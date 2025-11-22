<?php

namespace Abdullmng\Distance\DTOs;

/**
 * Structured address for more accurate geocoding.
 * Breaking down address into components helps geocoders find more accurate results.
 */
class StructuredAddress
{
    public function __construct(
        public readonly ?string $houseNumber = null,
        public readonly ?string $street = null,
        public readonly ?string $neighbourhood = null,
        public readonly ?string $suburb = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $country = null
    ) {
    }

    /**
     * Create from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            houseNumber: $data['house_number'] ?? $data['houseNumber'] ?? null,
            street: $data['street'] ?? null,
            neighbourhood: $data['neighbourhood'] ?? $data['neighborhood'] ?? null,
            suburb: $data['suburb'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? $data['province'] ?? null,
            postalCode: $data['postal_code'] ?? $data['postalCode'] ?? $data['zip'] ?? null,
            country: $data['country'] ?? null
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter([
            'house_number' => $this->houseNumber,
            'street' => $this->street,
            'neighbourhood' => $this->neighbourhood,
            'suburb' => $this->suburb,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ], fn($value) => $value !== null);
    }

    /**
     * Convert to a single-line address string.
     *
     * @return string
     */
    public function toString(): string
    {
        $parts = array_filter([
            $this->houseNumber ? "No. {$this->houseNumber}" : null,
            $this->street,
            $this->neighbourhood,
            $this->suburb,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Check if address has minimum required components.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // At minimum, we need either (street + city) or (suburb + city) or (city + country)
        return ($this->street && $this->city)
            || ($this->suburb && $this->city)
            || ($this->city && $this->country);
    }

    /**
     * Get quality score based on how many components are filled.
     * More components = higher quality/specificity.
     *
     * @return float 0-1 score
     */
    public function getQualityScore(): float
    {
        $components = [
            $this->houseNumber,
            $this->street,
            $this->neighbourhood,
            $this->suburb,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ];

        $filled = count(array_filter($components, fn($v) => $v !== null));
        $total = count($components);

        return $filled / $total;
    }
}

