<?php

/**
 * Basic Usage Examples for Laravel Distance Package
 * 
 * This file demonstrates common use cases for the package.
 */

use Abdullmng\Distance\Facades\Distance;
use Abdullmng\Distance\DTOs\Coordinate;

// ============================================
// Example 1: Calculate distance between cities
// ============================================

$result = Distance::between('New York, NY', 'Los Angeles, CA');

echo "Distance: {$result->distance} {$result->unit}\n";
echo "In miles: {$result->inMiles()} mi\n";
echo "In kilometers: {$result->inKilometers()} km\n";

// ============================================
// Example 2: Using coordinates
// ============================================

$newYork = new Coordinate(40.7128, -74.0060);
$losAngeles = new Coordinate(34.0522, -118.2437);

$result = Distance::between($newYork, $losAngeles, 'miles');
echo "Distance: {$result->distance} miles\n";

// ============================================
// Example 3: Geocoding addresses
// ============================================

$coordinate = Distance::geocode('1600 Amphitheatre Parkway, Mountain View, CA');

if ($coordinate) {
    echo "Latitude: {$coordinate->latitude}\n";
    echo "Longitude: {$coordinate->longitude}\n";
    echo "Address: {$coordinate->formattedAddress}\n";
}

// ============================================
// Example 4: Reverse geocoding
// ============================================

$address = Distance::reverse(37.4224764, -122.0842499);
echo "Address: {$address}\n";

// ============================================
// Example 5: Get bearing and direction
// ============================================

$bearing = Distance::bearing('New York, NY', 'Los Angeles, CA');
$direction = Distance::direction('New York, NY', 'Los Angeles, CA');

echo "Bearing: {$bearing}°\n";
echo "Direction: {$direction}\n";

// ============================================
// Example 6: Logistics - Delivery range check
// ============================================

function isWithinDeliveryRange(string $warehouse, string $customer, float $maxDistance = 50): bool
{
    $result = Distance::between($warehouse, $customer, 'kilometers');
    return $result->distance <= $maxDistance;
}

$warehouse = '123 Warehouse St, New York, NY';
$customer = '456 Customer Ave, Brooklyn, NY';

if (isWithinDeliveryRange($warehouse, $customer)) {
    echo "Customer is within delivery range\n";
} else {
    echo "Customer is outside delivery range\n";
}

// ============================================
// Example 7: Ride-sharing - Calculate ETA
// ============================================

function calculateETA(string $driverLocation, string $passengerLocation, float $avgSpeed = 30): float
{
    $result = Distance::between($driverLocation, $passengerLocation, 'miles');
    return ($result->distance / $avgSpeed) * 60; // ETA in minutes
}

$driver = '40.7580,-73.9855';
$passenger = '40.7489,-73.9680';
$eta = calculateETA($driver, $passenger);

echo "ETA: " . round($eta, 1) . " minutes\n";

// ============================================
// Example 8: Find nearest location
// ============================================

function findNearest(Coordinate $userLocation, array $locations): array
{
    $nearest = null;
    $minDistance = PHP_FLOAT_MAX;

    foreach ($locations as $name => $coordinate) {
        $result = Distance::between($userLocation, $coordinate, 'kilometers');
        
        if ($result->distance < $minDistance) {
            $minDistance = $result->distance;
            $nearest = [
                'name' => $name,
                'coordinate' => $coordinate,
                'distance' => $result->distance,
            ];
        }
    }

    return $nearest;
}

$userLocation = new Coordinate(40.7128, -74.0060); // New York

$stores = [
    'Store A' => new Coordinate(40.7580, -73.9855),
    'Store B' => new Coordinate(40.7489, -73.9680),
    'Store C' => new Coordinate(40.7614, -73.9776),
];

$nearest = findNearest($userLocation, $stores);
echo "Nearest store: {$nearest['name']} ({$nearest['distance']} km away)\n";

// ============================================
// Example 9: Using different units
// ============================================

$from = 'London, UK';
$to = 'Paris, France';

echo "Distance in kilometers: " . Distance::between($from, $to, 'kilometers')->distance . " km\n";
echo "Distance in miles: " . Distance::between($from, $to, 'miles')->distance . " mi\n";
echo "Distance in meters: " . Distance::between($from, $to, 'meters')->distance . " m\n";

// ============================================
// Example 10: More accurate calculation with Vincenty
// ============================================

$result = Distance::betweenVincenty('Sydney, Australia', 'Tokyo, Japan', 'kilometers');
echo "Accurate distance: {$result->distance} km\n";

