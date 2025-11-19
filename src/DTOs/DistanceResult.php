<?php

namespace Abdullmng\Distance\DTOs;

class DistanceResult
{
    public function __construct(
        public readonly Coordinate $from,
        public readonly Coordinate $to,
        public readonly float $distance,
        public readonly string $unit
    ) {
    }

    /**
     * Get distance in kilometers.
     *
     * @return float
     */
    public function inKilometers(): float
    {
        return match ($this->unit) {
            'kilometers', 'km' => $this->distance,
            'miles', 'mi' => $this->distance * 1.60934,
            'meters', 'm' => $this->distance / 1000,
            'feet', 'ft' => $this->distance * 0.0003048,
            default => $this->distance,
        };
    }

    /**
     * Get distance in miles.
     *
     * @return float
     */
    public function inMiles(): float
    {
        return match ($this->unit) {
            'kilometers', 'km' => $this->distance * 0.621371,
            'miles', 'mi' => $this->distance,
            'meters', 'm' => $this->distance * 0.000621371,
            'feet', 'ft' => $this->distance * 0.000189394,
            default => $this->distance,
        };
    }

    /**
     * Get distance in meters.
     *
     * @return float
     */
    public function inMeters(): float
    {
        return match ($this->unit) {
            'kilometers', 'km' => $this->distance * 1000,
            'miles', 'mi' => $this->distance * 1609.34,
            'meters', 'm' => $this->distance,
            'feet', 'ft' => $this->distance * 0.3048,
            default => $this->distance,
        };
    }

    /**
     * Get distance in feet.
     *
     * @return float
     */
    public function inFeet(): float
    {
        return match ($this->unit) {
            'kilometers', 'km' => $this->distance * 3280.84,
            'miles', 'mi' => $this->distance * 5280,
            'meters', 'm' => $this->distance * 3.28084,
            'feet', 'ft' => $this->distance,
            default => $this->distance,
        };
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->toArray(),
            'to' => $this->to->toArray(),
            'distance' => $this->distance,
            'unit' => $this->unit,
            'conversions' => [
                'kilometers' => round($this->inKilometers(), 2),
                'miles' => round($this->inMiles(), 2),
                'meters' => round($this->inMeters(), 2),
                'feet' => round($this->inFeet(), 2),
            ],
        ];
    }
}

