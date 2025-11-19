<?php

namespace Abdullmng\Distance\Tests\Unit;

use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\Services\DistanceCalculator;
use PHPUnit\Framework\TestCase;

class DistanceCalculatorTest extends TestCase
{
    private DistanceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DistanceCalculator();
    }

    public function test_calculates_distance_in_kilometers()
    {
        $newYork = new Coordinate(40.7128, -74.0060);
        $losAngeles = new Coordinate(34.0522, -118.2437);

        $distance = $this->calculator->calculate($newYork, $losAngeles, 'kilometers');

        // Distance between NY and LA is approximately 3944 km
        $this->assertGreaterThan(3900, $distance);
        $this->assertLessThan(4000, $distance);
    }

    public function test_calculates_distance_in_miles()
    {
        $newYork = new Coordinate(40.7128, -74.0060);
        $losAngeles = new Coordinate(34.0522, -118.2437);

        $distance = $this->calculator->calculate($newYork, $losAngeles, 'miles');

        // Distance between NY and LA is approximately 2451 miles
        $this->assertGreaterThan(2400, $distance);
        $this->assertLessThan(2500, $distance);
    }

    public function test_calculates_distance_in_meters()
    {
        $coord1 = new Coordinate(40.7128, -74.0060);
        $coord2 = new Coordinate(40.7580, -73.9855);

        $distance = $this->calculator->calculate($coord1, $coord2, 'meters');

        // Short distance should be in thousands of meters
        $this->assertGreaterThan(5000, $distance);
        $this->assertLessThan(10000, $distance);
    }

    public function test_calculates_zero_distance_for_same_location()
    {
        $coord = new Coordinate(40.7128, -74.0060);

        $distance = $this->calculator->calculate($coord, $coord, 'kilometers');

        $this->assertEqualsWithDelta(0, $distance, 0.001);
    }

    public function test_calculates_bearing()
    {
        $newYork = new Coordinate(40.7128, -74.0060);
        $losAngeles = new Coordinate(34.0522, -118.2437);

        $bearing = $this->calculator->bearing($newYork, $losAngeles);

        // Bearing from NY to LA should be roughly west (around 270 degrees)
        $this->assertGreaterThan(260, $bearing);
        $this->assertLessThan(280, $bearing);
    }

    public function test_gets_compass_direction()
    {
        $newYork = new Coordinate(40.7128, -74.0060);
        $losAngeles = new Coordinate(34.0522, -118.2437);

        $bearing = $this->calculator->bearing($newYork, $losAngeles);
        $direction = $this->calculator->compassDirection($bearing);

        $this->assertEquals('W', $direction);
    }

    public function test_compass_direction_for_north()
    {
        $direction = $this->calculator->compassDirection(0);
        $this->assertEquals('N', $direction);

        $direction = $this->calculator->compassDirection(360);
        $this->assertEquals('N', $direction);
    }

    public function test_compass_direction_for_east()
    {
        $direction = $this->calculator->compassDirection(90);
        $this->assertEquals('E', $direction);
    }

    public function test_compass_direction_for_south()
    {
        $direction = $this->calculator->compassDirection(180);
        $this->assertEquals('S', $direction);
    }

    public function test_compass_direction_for_west()
    {
        $direction = $this->calculator->compassDirection(270);
        $this->assertEquals('W', $direction);
    }

    public function test_vincenty_formula_calculates_distance()
    {
        $newYork = new Coordinate(40.7128, -74.0060);
        $losAngeles = new Coordinate(34.0522, -118.2437);

        $distance = $this->calculator->calculateVincenty($newYork, $losAngeles, 'kilometers');

        // Vincenty should give similar results to Haversine
        $this->assertGreaterThan(3900, $distance);
        $this->assertLessThan(4000, $distance);
    }
}
