<?php

namespace Abdullmng\Distance\Services;

use Abdullmng\Distance\DTOs\Coordinate;

class DistanceCalculator
{
    // Earth's radius in different units
    private const EARTH_RADIUS_KM = 6371;
    private const EARTH_RADIUS_MI = 3959;
    private const EARTH_RADIUS_M = 6371000;
    private const EARTH_RADIUS_FT = 20902231;

    /**
     * Calculate distance between two coordinates using the Haversine formula.
     *
     * @param Coordinate $from
     * @param Coordinate $to
     * @param string $unit
     * @return float
     */
    public function calculate(Coordinate $from, Coordinate $to, string $unit = 'kilometers'): float
    {
        $earthRadius = $this->getEarthRadius($unit);

        $latFrom = deg2rad($from->latitude);
        $lonFrom = deg2rad($from->longitude);
        $latTo = deg2rad($to->latitude);
        $lonTo = deg2rad($to->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        // Haversine formula
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate distance using the Vincenty formula (more accurate but slower).
     *
     * @param Coordinate $from
     * @param Coordinate $to
     * @param string $unit
     * @return float
     */
    public function calculateVincenty(Coordinate $from, Coordinate $to, string $unit = 'kilometers'): float
    {
        $earthRadius = $this->getEarthRadius($unit);

        $latFrom = deg2rad($from->latitude);
        $lonFrom = deg2rad($from->longitude);
        $latTo = deg2rad($to->latitude);
        $lonTo = deg2rad($to->longitude);

        $lonDelta = $lonTo - $lonFrom;

        $a = pow(cos($latTo) * sin($lonDelta), 2) +
             pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);

        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        return $earthRadius * $angle;
    }

    /**
     * Get Earth's radius for the specified unit.
     *
     * @param string $unit
     * @return float
     */
    private function getEarthRadius(string $unit): float
    {
        return match ($unit) {
            'kilometers', 'km' => self::EARTH_RADIUS_KM,
            'miles', 'mi' => self::EARTH_RADIUS_MI,
            'meters', 'm' => self::EARTH_RADIUS_M,
            'feet', 'ft' => self::EARTH_RADIUS_FT,
            default => self::EARTH_RADIUS_KM,
        };
    }

    /**
     * Calculate the bearing (direction) from one coordinate to another.
     *
     * @param Coordinate $from
     * @param Coordinate $to
     * @return float Bearing in degrees (0-360)
     */
    public function bearing(Coordinate $from, Coordinate $to): float
    {
        $latFrom = deg2rad($from->latitude);
        $lonFrom = deg2rad($from->longitude);
        $latTo = deg2rad($to->latitude);
        $lonTo = deg2rad($to->longitude);

        $lonDelta = $lonTo - $lonFrom;

        $y = sin($lonDelta) * cos($latTo);
        $x = cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta);

        $bearing = atan2($y, $x);
        $bearing = rad2deg($bearing);

        return fmod($bearing + 360, 360);
    }

    /**
     * Get compass direction from bearing.
     *
     * @param float $bearing
     * @return string
     */
    public function compassDirection(float $bearing): string
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        $index = round($bearing / 45) % 8;
        return $directions[$index];
    }
}

