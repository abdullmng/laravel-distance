<?php

namespace Abdullmng\Distance\DTOs;

class RouteResult
{
    public function __construct(
        public readonly Coordinate $from,
        public readonly Coordinate $to,
        public readonly float $distance,
        public readonly string $unit,
        public readonly ?float $duration = null, // in seconds
        public readonly ?array $steps = null,
        public readonly ?string $polyline = null,
        public readonly ?string $summary = null,
        public readonly string $type = 'route' // 'route' vs 'straight'
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
     * Get duration in minutes.
     *
     * @return float|null
     */
    public function durationInMinutes(): ?float
    {
        return $this->duration ? round($this->duration / 60, 2) : null;
    }

    /**
     * Get duration in hours.
     *
     * @return float|null
     */
    public function durationInHours(): ?float
    {
        return $this->duration ? round($this->duration / 3600, 2) : null;
    }

    /**
     * Get formatted duration (e.g., "2h 30m").
     *
     * @return string|null
     */
    public function formattedDuration(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Check if this is a route-based calculation.
     *
     * @return bool
     */
    public function isRoute(): bool
    {
        return $this->type === 'route';
    }

    /**
     * Check if this is a straight-line calculation.
     *
     * @return bool
     */
    public function isStraightLine(): bool
    {
        return $this->type === 'straight';
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
            'type' => $this->type,
            'duration' => $this->duration,
            'duration_formatted' => $this->formattedDuration(),
            'summary' => $this->summary,
            'conversions' => [
                'kilometers' => round($this->inKilometers(), 2),
                'miles' => round($this->inMiles(), 2),
                'meters' => round($this->inMeters(), 2),
                'feet' => round($this->inFeet(), 2),
            ],
        ];
    }
}

