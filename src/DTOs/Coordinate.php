<?php

namespace Abdullmng\Distance\DTOs;

class Coordinate
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?string $formattedAddress = null
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
            formattedAddress: $data['formatted_address'] ?? $data['address'] ?? null
        );
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

