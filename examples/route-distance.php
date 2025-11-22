<?php

require __DIR__ . '/../vendor/autoload.php';

use Abdullmng\Distance\Facades\Distance;
use Abdullmng\Distance\DTOs\Coordinate;

echo "==============================================\n";
echo "Route-Based Distance Calculation Examples\n";
echo "==============================================\n\n";

// ============================================
// Example 1: Straight-line vs Route Distance
// ============================================

echo "Example 1: Comparing Straight-line vs Route Distance\n";
echo "-----------------------------------------------------\n";

$from = 'Lagos, Nigeria';
$to = 'Abuja, Nigeria';

// Straight-line distance (as the crow flies)
$straightLine = Distance::between($from, $to);
echo "Straight-line distance: {$straightLine->distance} {$straightLine->unit}\n";
echo "  = {$straightLine->inMiles()} miles\n\n";

// Route-based distance (actual driving distance)
$route = Distance::route($from, $to);
echo "Route distance (driving): {$route->distance} {$route->unit}\n";
echo "  = {$route->inMiles()} miles\n";
echo "  Duration: {$route->formattedDuration()}\n";
echo "  Summary: {$route->summary}\n\n";

// ============================================
// Example 2: Different Travel Modes
// ============================================

echo "Example 2: Different Travel Modes\n";
echo "-----------------------------------------------------\n";

$warehouse = new Coordinate(6.5244, 3.3792); // Lagos
$customer = new Coordinate(9.0765, 7.3986); // Abuja

// Driving
$driving = Distance::route($warehouse, $customer, ['mode' => 'driving']);
echo "Driving:\n";
echo "  Distance: {$driving->inKilometers()} km\n";
echo "  Duration: {$driving->formattedDuration()}\n\n";

// Walking
$walking = Distance::route($warehouse, $customer, ['mode' => 'walking']);
echo "Walking:\n";
echo "  Distance: {$walking->inKilometers()} km\n";
echo "  Duration: {$walking->formattedDuration()}\n\n";

// Cycling
$cycling = Distance::route($warehouse, $customer, ['mode' => 'cycling']);
echo "Cycling:\n";
echo "  Distance: {$cycling->inKilometers()} km\n";
echo "  Duration: {$cycling->formattedDuration()}\n\n";

// ============================================
// Example 3: Logistics Use Case
// ============================================

echo "Example 3: Logistics Delivery Estimation\n";
echo "-----------------------------------------------------\n";

$deliveries = [
    ['name' => 'Customer A', 'address' => 'Ikeja, Lagos, Nigeria'],
    ['name' => 'Customer B', 'address' => 'Victoria Island, Lagos, Nigeria'],
    ['name' => 'Customer C', 'address' => 'Lekki, Lagos, Nigeria'],
];

$depot = 'Apapa, Lagos, Nigeria';

echo "Depot: {$depot}\n\n";

$totalDistance = 0;
$totalDuration = 0;

foreach ($deliveries as $delivery) {
    $route = Distance::route($depot, $delivery['address'], ['mode' => 'driving']);
    
    echo "{$delivery['name']} ({$delivery['address']}):\n";
    echo "  Distance: " . round($route->inKilometers(), 2) . " km\n";
    echo "  Duration: {$route->formattedDuration()}\n";
    echo "  ETA: " . date('H:i', time() + $route->duration) . "\n\n";
    
    $totalDistance += $route->inKilometers();
    $totalDuration += $route->duration;
}

echo "Total route distance: " . round($totalDistance, 2) . " km\n";
echo "Total estimated time: " . round($totalDuration / 60, 0) . " minutes\n\n";

// ============================================
// Example 4: Ride-Sharing Fare Estimation
// ============================================

echo "Example 4: Ride-Sharing Fare Estimation\n";
echo "-----------------------------------------------------\n";

$pickup = 'Gwarimpa, Abuja, Nigeria';
$dropoff = 'Maitama, Abuja, Nigeria';

$route = Distance::route($pickup, $dropoff, ['mode' => 'driving']);

// Simple fare calculation
$baseFare = 500; // NGN
$perKmRate = 150; // NGN per km
$perMinuteRate = 10; // NGN per minute

$distanceFare = $route->inKilometers() * $perKmRate;
$timeFare = $route->durationInMinutes() * $perMinuteRate;
$totalFare = $baseFare + $distanceFare + $timeFare;

echo "Pickup: {$pickup}\n";
echo "Dropoff: {$dropoff}\n\n";
echo "Route Details:\n";
echo "  Distance: " . round($route->inKilometers(), 2) . " km\n";
echo "  Duration: {$route->formattedDuration()}\n\n";
echo "Fare Breakdown:\n";
echo "  Base fare: ₦" . number_format($baseFare, 2) . "\n";
echo "  Distance charge: ₦" . number_format($distanceFare, 2) . "\n";
echo "  Time charge: ₦" . number_format($timeFare, 2) . "\n";
echo "  Total fare: ₦" . number_format($totalFare, 2) . "\n\n";

// ============================================
// Example 5: Route Result as Array
// ============================================

echo "Example 5: Route Result as Array (for API responses)\n";
echo "-----------------------------------------------------\n";

$route = Distance::route('Ikeja, Lagos', 'Lekki, Lagos', ['mode' => 'driving']);
$routeArray = $route->toArray();

echo json_encode($routeArray, JSON_PRETTY_PRINT) . "\n\n";

// ============================================
// Example 6: Checking Route Type
// ============================================

echo "Example 6: Checking Route Type\n";
echo "-----------------------------------------------------\n";

$straightLine = Distance::between('Point A', 'Point B');
$route = Distance::route('Point A', 'Point B');

echo "Is straight-line? " . ($straightLine instanceof \Abdullmng\Distance\DTOs\DistanceResult ? 'Yes' : 'No') . "\n";
echo "Is route? " . ($route->isRoute() ? 'Yes' : 'No') . "\n";
echo "Route type: {$route->type}\n\n";

echo "==============================================\n";
echo "Examples completed!\n";
echo "==============================================\n";

