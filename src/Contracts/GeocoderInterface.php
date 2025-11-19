<?php

namespace Abdullmng\Distance\Contracts;

use Abdullmng\Distance\DTOs\Coordinate;

interface GeocoderInterface
{
    /**
     * Geocode an address to coordinates.
     *
     * @param string $address
     * @return Coordinate|null
     * @throws \Abdullmng\Distance\Exceptions\GeocodingException
     */
    public function geocode(string $address): ?Coordinate;

    /**
     * Reverse geocode coordinates to an address.
     *
     * @param float $latitude
     * @param float $longitude
     * @return string|null
     * @throws \Abdullmng\Distance\Exceptions\GeocodingException
     */
    public function reverse(float $latitude, float $longitude): ?string;
}

