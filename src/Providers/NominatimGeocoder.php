<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\DTOs\Coordinate;
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
            $coordinate = new Coordinate(
                latitude: (float) $result['lat'],
                longitude: (float) $result['lon'],
                formattedAddress: $result['display_name'] ?? null
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
}

