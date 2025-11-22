<?php

namespace Abdullmng\Distance\DTOs;

class Coordinate
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $formattedAddress = null,
        public readonly ?float $accuracy = null, // Geocoding accuracy/quality score (0-1, 1 = highest)
        public readonly ?string $source = null, // Which geocoder provided this coordinate
        public readonly ?array $metadata = null // Additional metadata from geocoder
    ) {
        $this->validate();
    }

    /**
     * Validate coordinate values.
     *
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new \InvalidArgumentException("Latitude must be between -90 and 90 degrees. Got: {$this->latitude}");
        }

        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new \InvalidArgumentException("Longitude must be between -180 and 180 degrees. Got: {$this->longitude}");
        }
    }

    /**
     * Create a Coordinate from an array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            latitude: $data['latitude'] ?? $data['lat'],
            longitude: $data['longitude'] ?? $data['lng'] ?? $data['lon'],
            formattedAddress: $data['formatted_address'] ?? $data['address'] ?? null,
            accuracy: $data['accuracy'] ?? null,
            source: $data['source'] ?? null,
            metadata: $data['metadata'] ?? null
        );
    }

    /**
     * Check if this coordinate has high accuracy.
     *
     * @return bool
     */
    public function isHighAccuracy(): bool
    {
        return $this->accuracy !== null && $this->accuracy >= 0.8;
    }

    /**
     * Check if this coordinate has low accuracy.
     *
     * @return bool
     */
    public function isLowAccuracy(): bool
    {
        return $this->accuracy !== null && $this->accuracy < 0.5;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'formatted_address' => $this->formattedAddress,
            'accuracy' => $this->accuracy,
            'source' => $this->source,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }
}
