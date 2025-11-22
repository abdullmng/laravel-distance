<?php

namespace Abdullmng\Distance\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Abdullmng\Distance\DTOs\DistanceResult between(string|\Abdullmng\Distance\DTOs\Coordinate $from, string|\Abdullmng\Distance\DTOs\Coordinate $to, string|null $unit = null)
 * @method static \Abdullmng\Distance\DTOs\DistanceResult betweenVincenty(string|\Abdullmng\Distance\DTOs\Coordinate $from, string|\Abdullmng\Distance\DTOs\Coordinate $to, string|null $unit = null)
 * @method static \Abdullmng\Distance\DTOs\RouteResult route(string|\Abdullmng\Distance\DTOs\Coordinate $from, string|\Abdullmng\Distance\DTOs\Coordinate $to, array $options = [])
 * @method static \Abdullmng\Distance\DTOs\Coordinate|null geocode(string $address)
 * @method static \Abdullmng\Distance\DTOs\Coordinate|null geocodeStructured(\Abdullmng\Distance\DTOs\StructuredAddress $address)
 * @method static string|null reverse(float $latitude, float $longitude)
 * @method static float bearing(string|\Abdullmng\Distance\DTOs\Coordinate $from, string|\Abdullmng\Distance\DTOs\Coordinate $to)
 * @method static string direction(string|\Abdullmng\Distance\DTOs\Coordinate $from, string|\Abdullmng\Distance\DTOs\Coordinate $to)
 * @method static \Abdullmng\Distance\Distance unit(string $unit)
 *
 * @see \Abdullmng\Distance\Distance
 */
class Distance extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'distance';
    }
}
