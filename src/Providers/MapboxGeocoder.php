<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Exceptions\GeocodingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class MapboxGeocoder extends AbstractGeocoder
{
    public function __construct(array $config)
    {
        $this->config = $config;

        if (empty($config['api_key'])) {
            throw GeocodingException::noApiKey();
        }

        // Ensure base_uri ends with / for proper path resolution
        $baseUri = rtrim($config['url'], '/') . '/';

        $this->client = new Client([
            'base_uri' => $baseUri,
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
            // URL encode the address for the path
            $encodedAddress = rawurlencode($address);

            $response = $this->client->get("{$encodedAddress}.json", [
                'query' => [
                    'access_token' => $this->config['api_key'],
                    'limit' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['features'])) {
                throw GeocodingException::invalidAddress($address);
            }

            $feature = $data['features'][0];
            $coordinates = $feature['geometry']['coordinates'];

            // Calculate accuracy score
            $accuracy = $this->calculateAccuracy($feature, $address);

            $coordinate = new Coordinate(
                latitude: (float) $coordinates[1], // Mapbox returns [lng, lat]
                longitude: (float) $coordinates[0],
                formattedAddress: $feature['place_name'] ?? null,
                accuracy: $accuracy,
                source: 'mapbox',
                metadata: [
                    'relevance' => $feature['relevance'] ?? null,
                    'place_type' => $feature['place_type'] ?? null,
                    'context' => $feature['context'] ?? null,
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
        // Mapbox doesn't have native structured geocoding, so we build a formatted query
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
            // Mapbox reverse geocoding uses lng,lat format
            $response = $this->client->get("{$longitude},{$latitude}.json", [
                'query' => [
                    'access_token' => $this->config['api_key'],
                    'limit' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['features'])) {
                return null;
            }

            $address = $data['features'][0]['place_name'];

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $address, $this->getCacheDuration());
            }

            return $address;
        } catch (GuzzleException $e) {
            throw GeocodingException::apiError($e->getMessage());
        }
    }

    /**
     * Calculate accuracy score for Mapbox result.
     *
     * @param array $feature
     * @param string $originalAddress
     * @return float
     */
    private function calculateAccuracy(array $feature, string $originalAddress): float
    {
        $score = 0.5; // Base score

        // Mapbox provides relevance score (0-1)
        if (isset($feature['relevance'])) {
            $score += $feature['relevance'] * 0.4;
        }

        // Check place type
        $placeTypes = $feature['place_type'] ?? [];
        if (!empty($placeTypes)) {
            $type = $placeTypes[0];
            $typeAccuracy = $this->getTypeAccuracy($type);
            $score += $typeAccuracy * 0.3;
        }

        // Check word matching
        $matchAccuracy = $this->calculateMatchAccuracy($originalAddress, $feature['place_name'] ?? '');
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
        return "{$prefix}:mapbox:{$type}:" . md5($value);
    }
}
