<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Exceptions\GeocodingException;
use Illuminate\Support\Facades\Log;

/**
 * Fallback geocoder that tries multiple providers in order.
 * Useful for improving accuracy by trying free providers first,
 * then falling back to paid providers if quality is low.
 */
class FallbackGeocoder implements GeocoderInterface
{
    private array $providers = [];
    private float $minimumAccuracy;

    /**
     * @param array $providers Array of GeocoderInterface instances
     * @param float $minimumAccuracy Minimum accuracy threshold (0-1)
     */
    public function __construct(array $providers, float $minimumAccuracy = 0.5)
    {
        $this->providers = $providers;
        $this->minimumAccuracy = $minimumAccuracy;
    }

    /**
     * {@inheritdoc}
     */
    public function geocode(string $address): ?Coordinate
    {
        $lastException = null;
        $bestResult = null;

        foreach ($this->providers as $provider) {
            try {
                $result = $provider->geocode($address);

                if ($result === null) {
                    continue;
                }

                // If we have a high-accuracy result, return it immediately
                if ($result->accuracy !== null && $result->accuracy >= 0.8) {
                    return $result;
                }

                // Keep track of the best result so far
                if ($bestResult === null || 
                    ($result->accuracy ?? 0) > ($bestResult->accuracy ?? 0)) {
                    $bestResult = $result;
                }

                // If result meets minimum accuracy, return it
                if ($result->accuracy !== null && $result->accuracy >= $this->minimumAccuracy) {
                    return $result;
                }

            } catch (GeocodingException $e) {
                $lastException = $e;
                Log::warning("Geocoding failed with provider " . get_class($provider) . ": " . $e->getMessage());
                continue;
            }
        }

        // Return best result if we have one, even if below threshold
        if ($bestResult !== null) {
            return $bestResult;
        }

        // All providers failed
        if ($lastException) {
            throw $lastException;
        }

        throw GeocodingException::invalidAddress($address);
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeStructured(StructuredAddress $address): ?Coordinate
    {
        $lastException = null;
        $bestResult = null;

        foreach ($this->providers as $provider) {
            try {
                $result = $provider->geocodeStructured($address);

                if ($result === null) {
                    continue;
                }

                // If we have a high-accuracy result, return it immediately
                if ($result->accuracy !== null && $result->accuracy >= 0.8) {
                    return $result;
                }

                // Keep track of the best result so far
                if ($bestResult === null || 
                    ($result->accuracy ?? 0) > ($bestResult->accuracy ?? 0)) {
                    $bestResult = $result;
                }

                // If result meets minimum accuracy, return it
                if ($result->accuracy !== null && $result->accuracy >= $this->minimumAccuracy) {
                    return $result;
                }

            } catch (GeocodingException $e) {
                $lastException = $e;
                Log::warning("Structured geocoding failed with provider " . get_class($provider) . ": " . $e->getMessage());
                continue;
            }
        }

        // Return best result if we have one, even if below threshold
        if ($bestResult !== null) {
            return $bestResult;
        }

        // All providers failed
        if ($lastException) {
            throw $lastException;
        }

        throw GeocodingException::invalidAddress($address->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(float $latitude, float $longitude): ?string
    {
        $lastException = null;

        foreach ($this->providers as $provider) {
            try {
                $result = $provider->reverse($latitude, $longitude);
                
                if ($result !== null) {
                    return $result;
                }
            } catch (GeocodingException $e) {
                $lastException = $e;
                Log::warning("Reverse geocoding failed with provider " . get_class($provider) . ": " . $e->getMessage());
                continue;
            }
        }

        // All providers failed
        if ($lastException) {
            throw $lastException;
        }

        return null;
    }
}

