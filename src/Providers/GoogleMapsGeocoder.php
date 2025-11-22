<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Exceptions\GeocodingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class GoogleMapsGeocoder extends AbstractGeocoder
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
                    'address' => $address,
                    'key' => $this->config['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                if ($data['status'] === 'ZERO_RESULTS') {
                    throw GeocodingException::invalidAddress($address);
                }
                throw GeocodingException::apiError($data['status'] ?? 'Unknown error');
            }

            $result = $data['results'][0];
            $location = $result['geometry']['location'];

            // Calculate accuracy score
            $accuracy = $this->calculateAccuracy($result, $address);

            $coordinate = new Coordinate(
                latitude: (float) $location['lat'],
                longitude: (float) $location['lng'],
                formattedAddress: $result['formatted_address'] ?? null,
                accuracy: $accuracy,
                source: 'google',
                metadata: [
                    'location_type' => $result['geometry']['location_type'] ?? null,
                    'types' => $result['types'] ?? null,
                    'place_id' => $result['place_id'] ?? null,
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
        // Google Maps supports structured geocoding via address components
        $components = [];

        if ($address->street) {
            $route = $address->houseNumber ? "{$address->houseNumber} {$address->street}" : $address->street;
            $components[] = "route:{$route}";
        }

        if ($address->suburb) {
            $components[] = "sublocality:{$address->suburb}";
        }

        if ($address->city) {
            $components[] = "locality:{$address->city}";
        }

        if ($address->state) {
            $components[] = "administrative_area:{$address->state}";
        }

        if ($address->postalCode) {
            $components[] = "postal_code:{$address->postalCode}";
        }

        if ($address->country) {
            $components[] = "country:{$address->country}";
        }

        $cacheKey = $this->getCacheKey('geocode_structured', implode('|', $components));

        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = $this->client->get('', [
                'query' => [
                    'components' => implode('|', $components),
                    'key' => $this->config['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                // Fallback to regular geocoding
                return $this->geocode($address->toString());
            }

            $result = $data['results'][0];
            $location = $result['geometry']['location'];

            // Calculate accuracy with structured bonus
            $baseAccuracy = $this->calculateAccuracy($result, $address->toString());
            $structuredBonus = $address->getQualityScore() * 0.2; // Google is better with structured
            $accuracy = min(1.0, $baseAccuracy + $structuredBonus);

            $coordinate = new Coordinate(
                latitude: (float) $location['lat'],
                longitude: (float) $location['lng'],
                formattedAddress: $result['formatted_address'] ?? null,
                accuracy: $accuracy,
                source: 'google',
                metadata: [
                    'location_type' => $result['geometry']['location_type'] ?? null,
                    'types' => $result['types'] ?? null,
                    'place_id' => $result['place_id'] ?? null,
                    'structured' => true,
                ]
            );

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $coordinate, $this->getCacheDuration());
            }

            return $coordinate;
        } catch (GuzzleException $e) {
            // Fallback to regular geocoding
            return $this->geocode($address->toString());
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
            $response = $this->client->get('', [
                'query' => [
                    'latlng' => "{$latitude},{$longitude}",
                    'key' => $this->config['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return null;
            }

            $address = $data['results'][0]['formatted_address'];

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $address, $this->getCacheDuration());
            }

            return $address;
        } catch (GuzzleException $e) {
            throw GeocodingException::apiError($e->getMessage());
        }
    }

    /**
     * Calculate accuracy score for Google Maps result.
     *
     * @param array $result
     * @param string $originalAddress
     * @return float
     */
    private function calculateAccuracy(array $result, string $originalAddress): float
    {
        $score = 0.6; // Google has higher base score

        // Google provides location_type which indicates accuracy
        $locationType = $result['geometry']['location_type'] ?? '';
        $locationTypeScore = match ($locationType) {
            'ROOFTOP' => 0.9,           // Most accurate
            'RANGE_INTERPOLATED' => 0.7, // Interpolated between two points
            'GEOMETRIC_CENTER' => 0.5,   // Center of a location
            'APPROXIMATE' => 0.3,        // Approximate location
            default => 0.5,
        };
        $score += $locationTypeScore * 0.3;

        // Check result types
        $types = $result['types'] ?? [];
        if (!empty($types)) {
            $type = $types[0];
            $typeAccuracy = $this->getTypeAccuracy($type);
            $score += $typeAccuracy * 0.3;
        }

        // Check word matching
        $matchAccuracy = $this->calculateMatchAccuracy($originalAddress, $result['formatted_address'] ?? '');
        $score += $matchAccuracy * 0.2;

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
        return "{$prefix}:google:{$type}:" . md5($value);
    }
}
