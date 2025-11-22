<?php

namespace Abdullmng\Distance;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\Contracts\RoutingInterface;
use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\DistanceResult;
use Abdullmng\Distance\DTOs\RouteResult;
use Abdullmng\Distance\Services\DistanceCalculator;

class Distance
{
    private GeocoderInterface $geocoder;
    private DistanceCalculator $calculator;
    private RoutingInterface $router;
    private string $unit;

    public function __construct(
        GeocoderInterface $geocoder,
        DistanceCalculator $calculator,
        RoutingInterface $router,
        string $unit = 'kilometers'
    ) {
        $this->geocoder = $geocoder;
        $this->calculator = $calculator;
        $this->router = $router;
        $this->unit = $unit;
    }

    /**
     * Calculate distance between two locations (addresses or coordinates).
     *
     * @param string|Coordinate $from
     * @param string|Coordinate $to
     * @param string|null $unit
     * @return DistanceResult
     */
    public function between($from, $to, ?string $unit = null): DistanceResult
    {
        $fromCoordinate = $this->resolveCoordinate($from);
        $toCoordinate = $this->resolveCoordinate($to);
        $unit = $unit ?? $this->unit;

        $distance = $this->calculator->calculate($fromCoordinate, $toCoordinate, $unit);

        return new DistanceResult($fromCoordinate, $toCoordinate, $distance, $unit);
    }

    /**
     * Calculate distance using Vincenty formula (more accurate).
     *
     * @param string|Coordinate $from
     * @param string|Coordinate $to
     * @param string|null $unit
     * @return DistanceResult
     */
    public function betweenVincenty($from, $to, ?string $unit = null): DistanceResult
    {
        $fromCoordinate = $this->resolveCoordinate($from);
        $toCoordinate = $this->resolveCoordinate($to);
        $unit = $unit ?? $this->unit;

        $distance = $this->calculator->calculateVincenty($fromCoordinate, $toCoordinate, $unit);

        return new DistanceResult($fromCoordinate, $toCoordinate, $distance, $unit);
    }

    /**
     * Geocode an address to coordinates.
     *
     * @param string $address
     * @return Coordinate|null
     */
    public function geocode(string $address): ?Coordinate
    {
        return $this->geocoder->geocode($address);
    }

    /**
     * Geocode a structured address to coordinates.
     *
     * @param \Abdullmng\Distance\DTOs\StructuredAddress $address
     * @return Coordinate|null
     */
    public function geocodeStructured(\Abdullmng\Distance\DTOs\StructuredAddress $address): ?Coordinate
    {
        return $this->geocoder->geocodeStructured($address);
    }

    /**
     * Reverse geocode coordinates to an address.
     *
     * @param float $latitude
     * @param float $longitude
     * @return string|null
     */
    public function reverse(float $latitude, float $longitude): ?string
    {
        return $this->geocoder->reverse($latitude, $longitude);
    }

    /**
     * Get the bearing (direction) from one location to another.
     *
     * @param string|Coordinate $from
     * @param string|Coordinate $to
     * @return float
     */
    public function bearing($from, $to): float
    {
        $fromCoordinate = $this->resolveCoordinate($from);
        $toCoordinate = $this->resolveCoordinate($to);

        return $this->calculator->bearing($fromCoordinate, $toCoordinate);
    }

    /**
     * Get the compass direction from one location to another.
     *
     * @param string|Coordinate $from
     * @param string|Coordinate $to
     * @return string
     */
    public function direction($from, $to): string
    {
        $bearing = $this->bearing($from, $to);
        return $this->calculator->compassDirection($bearing);
    }

    /**
     * Calculate route-based distance between two locations.
     *
     * @param string|Coordinate $from
     * @param string|Coordinate $to
     * @param array $options Additional options (e.g., mode: driving, walking, cycling)
     * @return RouteResult
     */
    public function route($from, $to, array $options = []): RouteResult
    {
        $fromCoordinate = $this->resolveCoordinate($from);
        $toCoordinate = $this->resolveCoordinate($to);

        // Merge with default routing mode from config
        if (!isset($options['mode'])) {
            $options['mode'] = config('distance.routing_mode', 'driving');
        }

        return $this->router->route($fromCoordinate, $toCoordinate, $options);
    }

    /**
     * Set the default unit for distance calculations.
     *
     * @param string $unit
     * @return self
     */
    public function unit(string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * Resolve a location to a Coordinate object.
     *
     * @param string|Coordinate $location
     * @return Coordinate
     */
    private function resolveCoordinate($location): Coordinate
    {
        if ($location instanceof Coordinate) {
            return $location;
        }

        if (is_string($location)) {
            // Check if it's a coordinate string (e.g., "40.7128,-74.0060")
            if (preg_match('/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/', $location, $matches)) {
                return new Coordinate((float) $matches[1], (float) $matches[2]);
            }

            // Otherwise, geocode the address
            return $this->geocoder->geocode($location);
        }

        throw new \InvalidArgumentException('Location must be a string address, coordinate string, or Coordinate object');
    }
}
