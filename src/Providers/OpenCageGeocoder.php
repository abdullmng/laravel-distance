<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\Exceptions\GeocodingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class OpenCageGeocoder implements GeocoderInterface
{
    private Client $client;
    private array $config;

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
                    'no_annotations' => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['results'])) {
                throw GeocodingException::invalidAddress($address);
            }

            $result = $data['results'][0];
            $geometry = $result['geometry'];

            $coordinate = new Coordinate(
                latitude: (float) $geometry['lat'],
                longitude: (float) $geometry['lng'],
                formattedAddress: $result['formatted'] ?? null
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
        return "{$prefix}:opencage:{$type}:" . md5($value);
    }
}

