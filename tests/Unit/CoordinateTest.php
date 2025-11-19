<?php

namespace Abdullmng\Distance\Tests\Unit;

use Abdullmng\Distance\DTOs\Coordinate;
use PHPUnit\Framework\TestCase;

class CoordinateTest extends TestCase
{
    public function test_can_create_coordinate()
    {
        $coordinate = new Coordinate(40.7128, -74.0060);

        $this->assertEquals(40.7128, $coordinate->latitude);
        $this->assertEquals(-74.0060, $coordinate->longitude);
    }

    public function test_can_create_coordinate_with_formatted_address()
    {
        $coordinate = new Coordinate(
            40.7128,
            -74.0060,
            'New York, NY, USA'
        );

        $this->assertEquals('New York, NY, USA', $coordinate->formattedAddress);
    }

    public function test_validates_latitude_range()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Latitude must be between -90 and 90 degrees');

        new Coordinate(91, 0);
    }

    public function test_validates_longitude_range()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Longitude must be between -180 and 180 degrees');

        new Coordinate(0, 181);
    }

    public function test_can_create_from_array()
    {
        $data = [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'formatted_address' => 'New York, NY, USA',
        ];

        $coordinate = Coordinate::fromArray($data);

        $this->assertEquals(40.7128, $coordinate->latitude);
        $this->assertEquals(-74.0060, $coordinate->longitude);
        $this->assertEquals('New York, NY, USA', $coordinate->formattedAddress);
    }

    public function test_can_create_from_array_with_alternative_keys()
    {
        $data = [
            'lat' => 40.7128,
            'lng' => -74.0060,
        ];

        $coordinate = Coordinate::fromArray($data);

        $this->assertEquals(40.7128, $coordinate->latitude);
        $this->assertEquals(-74.0060, $coordinate->longitude);
    }

    public function test_can_convert_to_array()
    {
        $coordinate = new Coordinate(40.7128, -74.0060, 'New York, NY, USA');
        $array = $coordinate->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(40.7128, $array['latitude']);
        $this->assertEquals(-74.0060, $array['longitude']);
        $this->assertEquals('New York, NY, USA', $array['formatted_address']);
    }

    public function test_can_convert_to_string()
    {
        $coordinate = new Coordinate(40.7128, -74.0060);
        $string = (string) $coordinate;

        $this->assertEquals('40.7128,-74.006', $string);
    }
}

