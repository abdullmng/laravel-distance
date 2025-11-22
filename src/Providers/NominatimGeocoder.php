<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Exceptions\GeocodingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class NominatimGeocoder implements GeocoderInterface
{
    private Client $client;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => $config['url'],
            'timeout' => $config['timeout'] ?? 10,
            'headers' => [
                'User-Agent' => $config['user_agent'] ?? 'LaravelDistancePackage/1.0',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function geocode(string $address): ?Coordinate
    {
        $cacheKey = $this->getCacheKey('geocode', $address);

        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = $this->client->get('/search', [
                'query' => [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data)) {
                throw GeocodingException::invalidAddress($address);
            }

            $result = $data[0];

            // Calculate accuracy based on result quality
            $accuracy = $this->calculateAccuracy($result, $address);

            $coordinate = new Coordinate(
                latitude: (float) $result['lat'],
                longitude: (float) $result['lon'],
                formattedAddress: $result['display_name'] ?? null,
                accuracy: $accuracy,
                source: 'nominatim',
                metadata: [
                    'place_id' => $result['place_id'] ?? null,
                    'osm_type' => $result['osm_type'] ?? null,
                    'osm_id' => $result['osm_id'] ?? null,
                    'type' => $result['type'] ?? null,
                    'importance' => $result['importance'] ?? null,
                ]
            );

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $coordinate, $this->getCacheDuration());
            }

            return $coordinate;
        } catch (GuzzleException $e) {
            throw GeocodingException::apiError($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeStructured(StructuredAddress $address): ?Coordinate
    {
        $cacheKey = $this->getCacheKey('geocode_structured', $address->toString());

        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Build structured query parameters
            $queryParams = array_filter([
                'street' => $address->street ? ($address->houseNumber ? "{$address->houseNumber} {$address->street}" : $address->street) : null,
                'city' => $address->city,
                'county' => $address->suburb,
                'state' => $address->state,
                'country' => $address->country,
                'postalcode' => $address->postalCode,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1,
            ], fn($value) => $value !== null && $value !== '');

            $response = $this->client->get('/search', [
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data)) {
                throw GeocodingException::invalidAddress($address->toString());
            }

            $result = $data[0];

            // Structured addresses typically have higher accuracy
            $baseAccuracy = $this->calculateAccuracy($result, $address->toString());
            $structuredBonus = $address->getQualityScore() * 0.2; // Up to 20% bonus
            $accuracy = min(1.0, $baseAccuracy + $structuredBonus);

            $coordinate = new Coordinate(
                latitude: (float) $result['lat'],
                longitude: (float) $result['lon'],
                formattedAddress: $result['display_name'] ?? null,
                accuracy: $accuracy,
                source: 'nominatim',
                metadata: [
                    'place_id' => $result['place_id'] ?? null,
                    'osm_type' => $result['osm_type'] ?? null,
                    'osm_id' => $result['osm_id'] ?? null,
                    'type' => $result['type'] ?? null,
                    'importance' => $result['importance'] ?? null,
                    'structured' => true,
                ]
            );

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $coordinate, $this->getCacheDuration());
            }

            return $coordinate;
        } catch (GuzzleException $e) {
            throw GeocodingException::apiError($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(float $latitude, float $longitude): ?string
    {
        $cacheKey = $this->getCacheKey('reverse', "{$latitude},{$longitude}");

        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = $this->client->get('/reverse', [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'format' => 'json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data) || !isset($data['display_name'])) {
                return null;
            }

            $address = $data['display_name'];

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $address, $this->getCacheDuration());
            }

            return $address;
        } catch (GuzzleException $e) {
            throw GeocodingException::apiError($e->getMessage());
        }
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    private function isCacheEnabled(): bool
    {
        return config('distance.cache.enabled', true);
    }

    /**
     * Get cache duration in seconds.
     *
     * @return int
     */
    private function getCacheDuration(): int
    {
        return config('distance.cache.duration', 1440) * 60;
    }

    /**
     * Generate cache key.
     *
     * @param string $type
     * @param string $value
     * @return string
     */
    private function getCacheKey(string $type, string $value): string
    {
        $prefix = config('distance.cache.prefix', 'geocoding');
        return "{$prefix}:nominatim:{$type}:" . md5($value);
    }

    /**
     * Calculate accuracy score based on geocoding result quality.
     *
     * @param array $result
     * @param string $originalAddress
     * @return float 0-1 score (1 = highest accuracy)
     */
    private function calculateAccuracy(array $result, string $originalAddress): float
    {
        $score = 0.5; // Base score

        // Higher importance = better match
        if (isset($result['importance'])) {
            $score += $result['importance'] * 0.3;
        }

        // Specific place types are more accurate
        $accurateTypes = ['house', 'building', 'address', 'street'];
        $moderateTypes = ['neighbourhood', 'suburb', 'quarter', 'residential'];
        $lowTypes = ['city', 'town', 'village', 'state', 'country'];

        $type = $result['type'] ?? '';

        if (in_array($type, $accurateTypes)) {
            $score += 0.3;
        } elseif (in_array($type, $moderateTypes)) {
            $score += 0.15;
        } elseif (in_array($type, $lowTypes)) {
            $score -= 0.1;
        }

        // Check if returned address contains key parts of original
        $displayName = strtolower($result['display_name'] ?? '');
        $originalLower = strtolower($originalAddress);

        // Extract key words from original address (ignore common words)
        $commonWords = ['no', 'number', 'street', 'st', 'avenue', 'ave', 'road', 'rd', 'estate'];
        $originalWords = array_filter(
            preg_split('/[\s,]+/', $originalLower),
            fn($word) => strlen($word) > 2 && !in_array($word, $commonWords)
        );

        if (!empty($originalWords)) {
            $matchedWords = 0;
            foreach ($originalWords as $word) {
                if (str_contains($displayName, $word)) {
                    $matchedWords++;
                }
            }
            $matchRatio = $matchedWords / count($originalWords);
            $score += $matchRatio * 0.2;
        }

        // Ensure score is between 0 and 1
        return max(0.0, min(1.0, $score));
    }
}
