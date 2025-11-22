<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Traits\CalculatesGeocodingAccuracy;
use GuzzleHttp\Client;

abstract class AbstractGeocoder implements GeocoderInterface
{
    use CalculatesGeocodingAccuracy;

    protected Client $client;
    protected array $config;

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return config('distance.cache.enabled', true);
    }

    /**
     * Get cache duration in seconds.
     *
     * @return int
     */
    protected function getCacheDuration(): int
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
    protected function getCacheKey(string $type, string $value): string
    {
        $prefix = config('distance.cache.prefix', 'geocoding');
        $provider = strtolower(class_basename($this));
        return "{$prefix}:{$provider}:{$type}:" . md5($value);
    }

    /**
     * Default implementation of geocodeStructured that falls back to regular geocode.
     * Subclasses should override this if they support structured geocoding natively.
     *
     * @param StructuredAddress $address
     * @return \Abdullmng\Distance\DTOs\Coordinate|null
     */
    public function geocodeStructured(StructuredAddress $address): ?\Abdullmng\Distance\DTOs\Coordinate
    {
        // Default: convert to string and use regular geocode
        $coordinate = $this->geocode($address->toString());
        
        if ($coordinate && $coordinate->accuracy !== null) {
            // Boost accuracy for structured addresses
            $structuredBonus = $address->getQualityScore() * 0.15;
            $newAccuracy = min(1.0, $coordinate->accuracy + $structuredBonus);
            
            return new \Abdullmng\Distance\DTOs\Coordinate(
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
}

