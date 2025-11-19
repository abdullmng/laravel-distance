<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\Exceptions\GeocodingException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class GoogleMapsGeocoder implements GeocoderInterface
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

            $coordinate = new Coordinate(
                latitude: (float) $location['lat'],
                longitude: (float) $location['lng'],
                formattedAddress: $result['formatted_address'] ?? null
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
        return "{$prefix}:google:{$type}:" . md5($value);
    }
}

