<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Exceptions\GeocodingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class OpenCageGeocoder extends AbstractGeocoder
{
    public function __construct(array $config)
    {
        $this->config = $config;

        if (empty($config['api_key'])) {
            throw GeocodingException::noApiKey();
        }

        $this->client = new Client([
            'base_uri' => $config['url'],
            'timeout' => $config['timeout'] ?? 10,
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
            $response = $this->client->get('', [
                'query' => [
                    'q' => $address,
                    'key' => $this->config['api_key'],
                    'limit' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['results'])) {
                throw GeocodingException::invalidAddress($address);
            }

            $result = $data['results'][0];
            $geometry = $result['geometry'];

            // Calculate accuracy score
            $accuracy = $this->calculateAccuracy($result, $address);

            $coordinate = new Coordinate(
                latitude: (float) $geometry['lat'],
                longitude: (float) $geometry['lng'],
                formattedAddress: $result['formatted'] ?? null,
                accuracy: $accuracy,
                source: 'opencage',
                metadata: [
                    'confidence' => $result['confidence'] ?? null,
                    'components' => $result['components'] ?? null,
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
        // OpenCage doesn't have native structured geocoding, so we build a formatted query
        $queryParts = array_filter([
            $address->houseNumber ? "No. {$address->houseNumber}" : null,
            $address->street,
            $address->neighbourhood,
            $address->suburb,
            $address->city,
            $address->state,
            $address->postalCode,
            $address->country,
        ]);

        $query = implode(', ', $queryParts);

        $coordinate = $this->geocode($query);

        if ($coordinate && $coordinate->accuracy !== null) {
            // Boost accuracy for structured addresses
            $structuredBonus = $address->getQualityScore() * 0.15;
            $newAccuracy = min(1.0, $coordinate->accuracy + $structuredBonus);

            return new Coordinate(
                latitude: $coordinate->latitude,
                longitude: $coordinate->longitude,
                formattedAddress: $coordinate->formattedAddress,
                accuracy: $newAccuracy,
                source: $coordinate->source,
                metadata: array_merge($coordinate->metadata ?? [], ['structured' => true])
            );
        }

        return $coordinate;
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
            $response = $this->client->get('', [
                'query' => [
                    'q' => "{$latitude},{$longitude}",
                    'key' => $this->config['api_key'],
                    'limit' => 1,
                    'no_annotations' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['results'])) {
                return null;
            }

            $address = $data['results'][0]['formatted'];

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $address, $this->getCacheDuration());
            }

            return $address;
        } catch (GuzzleException $e) {
            throw GeocodingException::apiError($e->getMessage());
        }
    }

    /**
     * Calculate accuracy score for OpenCage result.
     *
     * @param array $result
     * @param string $originalAddress
     * @return float
     */
    private function calculateAccuracy(array $result, string $originalAddress): float
    {
        $score = 0.5; // Base score

        // OpenCage provides confidence score (1-10)
        if (isset($result['confidence'])) {
            $score += ($result['confidence'] / 10) * 0.4;
        }

        // Check result type/category
        $type = $result['components']['_type'] ?? '';
        $typeAccuracy = $this->getTypeAccuracy($type);
        $score += $typeAccuracy * 0.3;

        // Check word matching
        $matchAccuracy = $this->calculateMatchAccuracy($originalAddress, $result['formatted'] ?? '');
        $score += $matchAccuracy * 0.3;

        return max(0.0, min(1.0, $score));
    }

    /**
     * Get cache key with provider prefix.
     *
     * @param string $type
     * @param string $value
     * @return string
     */
    protected function getCacheKey(string $type, string $value): string
    {
        $prefix = config('distance.cache.prefix', 'geocoding');
        return "{$prefix}:opencage:{$type}:" . md5($value);
    }
}
