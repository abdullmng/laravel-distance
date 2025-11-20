<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\Exceptions\GeocodingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class MapboxGeocoder implements GeocoderInterface
{
    private Client $client;
    private array $config;

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

            $coordinate = new Coordinate(
                latitude: (float) $coordinates[1], // Mapbox returns [lng, lat]
                longitude: (float) $coordinates[0],
                formattedAddress: $feature['place_name'] ?? null
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
        return "{$prefix}:mapbox:{$type}:" . md5($value);
    }
}
