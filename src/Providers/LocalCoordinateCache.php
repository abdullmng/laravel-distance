<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\StructuredAddress;

/**
 * Local coordinate cache for known accurate addresses.
 * This is useful for frequently used addresses like warehouses, offices, etc.
 * Acts as a first-level cache before hitting external geocoding APIs.
 */
class LocalCoordinateCache implements GeocoderInterface
{
    private array $coordinates = [];
    private ?GeocoderInterface $fallbackGeocoder;

    /**
     * @param array $coordinates Array of address => Coordinate mappings
     * @param GeocoderInterface|null $fallbackGeocoder Geocoder to use if address not in cache
     */
    public function __construct(array $coordinates = [], ?GeocoderInterface $fallbackGeocoder = null)
    {
        $this->coordinates = $coordinates;
        $this->fallbackGeocoder = $fallbackGeocoder;
    }

    /**
     * Add a known coordinate to the cache.
     *
     * @param string $address
     * @param Coordinate $coordinate
     * @return void
     */
    public function add(string $address, Coordinate $coordinate): void
    {
        $key = $this->normalizeAddress($address);
        $this->coordinates[$key] = $coordinate;
    }

    /**
     * Add multiple coordinates at once.
     *
     * @param array $coordinates
     * @return void
     */
    public function addMany(array $coordinates): void
    {
        foreach ($coordinates as $address => $coordinate) {
            $this->add($address, $coordinate);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function geocode(string $address): ?Coordinate
    {
        $key = $this->normalizeAddress($address);

        // Check if we have this address in our local cache
        if (isset($this->coordinates[$key])) {
            $cached = $this->coordinates[$key];
            
            // Ensure cached coordinate has high accuracy marker
            if ($cached->accuracy === null || $cached->accuracy < 1.0) {
                return new Coordinate(
                    latitude: $cached->latitude,
                    longitude: $cached->longitude,
                    formattedAddress: $cached->formattedAddress ?? $address,
                    accuracy: 1.0, // Local cache is always 100% accurate
                    source: 'local_cache',
                    metadata: array_merge($cached->metadata ?? [], ['cached' => true])
                );
            }
            
            return $cached;
        }

        // Fall back to external geocoder if available
        if ($this->fallbackGeocoder) {
            $result = $this->fallbackGeocoder->geocode($address);
            
            // Optionally cache the result for future use
            if ($result && $result->isHighAccuracy()) {
                $this->add($address, $result);
            }
            
            return $result;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeStructured(StructuredAddress $address): ?Coordinate
    {
        // Try with the full address string first
        $result = $this->geocode($address->toString());
        
        if ($result) {
            return $result;
        }

        // Fall back to external geocoder if available
        if ($this->fallbackGeocoder) {
            $result = $this->fallbackGeocoder->geocodeStructured($address);
            
            // Cache high-accuracy results
            if ($result && $result->isHighAccuracy()) {
                $this->add($address->toString(), $result);
            }
            
            return $result;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(float $latitude, float $longitude): ?string
    {
        // Check if we have this coordinate in our cache
        foreach ($this->coordinates as $address => $coordinate) {
            // Check if coordinates match (within small tolerance)
            if (abs($coordinate->latitude - $latitude) < 0.0001 && 
                abs($coordinate->longitude - $longitude) < 0.0001) {
                return $coordinate->formattedAddress ?? $address;
            }
        }

        // Fall back to external geocoder
        if ($this->fallbackGeocoder) {
            return $this->fallbackGeocoder->reverse($latitude, $longitude);
        }

        return null;
    }

    /**
     * Normalize address for consistent matching.
     *
     * @param string $address
     * @return string
     */
    private function normalizeAddress(string $address): string
    {
        // Convert to lowercase and remove extra whitespace
        $normalized = strtolower(trim($address));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        // Remove common punctuation
        $normalized = str_replace([',', '.', ';'], '', $normalized);
        
        return $normalized;
    }

    /**
     * Get all cached coordinates.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->coordinates;
    }

    /**
     * Clear all cached coordinates.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->coordinates = [];
    }
}

