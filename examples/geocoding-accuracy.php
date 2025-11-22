<?php

require __DIR__ . '/../vendor/autoload.php';

use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Facades\Distance;

echo "=== Laravel Distance Package - Geocoding Accuracy Examples ===\n\n";

// Example 1: Simple Geocoding with Quality Check
echo "Example 1: Simple Geocoding with Quality Check\n";
echo str_repeat('-', 50) . "\n";

$address = "Gwarimpa, Abuja, Nigeria";
$coordinate = Distance::geocode($address);

if ($coordinate) {
    echo "Address: {$address}\n";
    echo "Coordinates: {$coordinate->latitude}, {$coordinate->longitude}\n";
    echo "Formatted: {$coordinate->formattedAddress}\n";
    echo "Accuracy: " . ($coordinate->accuracy * 100) . "%\n";
    echo "Source: {$coordinate->source}\n";
    
    if ($coordinate->isHighAccuracy()) {
        echo "✓ High accuracy result - safe to use\n";
    } elseif ($coordinate->isLowAccuracy()) {
        echo "⚠ Low accuracy result - may be inaccurate\n";
    } else {
        echo "~ Moderate accuracy result\n";
    }
} else {
    echo "❌ Could not geocode address\n";
}

echo "\n";

// Example 2: Structured Geocoding
echo "Example 2: Structured Geocoding\n";
echo str_repeat('-', 50) . "\n";

$structuredAddress = new StructuredAddress(
    houseNumber: '24',
    street: 'Obi Okosi Street',
    neighbourhood: 'Hill-Side Estate',
    suburb: 'Gwarimpa',
    city: 'Abuja',
    country: 'Nigeria'
);

echo "Structured Address:\n";
echo "  House Number: {$structuredAddress->houseNumber}\n";
echo "  Street: {$structuredAddress->street}\n";
echo "  Neighbourhood: {$structuredAddress->neighbourhood}\n";
echo "  Suburb: {$structuredAddress->suburb}\n";
echo "  City: {$structuredAddress->city}\n";
echo "  Country: {$structuredAddress->country}\n";
echo "  Quality Score: " . ($structuredAddress->getQualityScore() * 100) . "%\n\n";

$coordinate = Distance::geocodeStructured($structuredAddress);

if ($coordinate) {
    echo "Result:\n";
    echo "  Coordinates: {$coordinate->latitude}, {$coordinate->longitude}\n";
    echo "  Formatted: {$coordinate->formattedAddress}\n";
    echo "  Accuracy: " . ($coordinate->accuracy * 100) . "%\n";
    echo "  Source: {$coordinate->source}\n";
    
    if ($coordinate->metadata && isset($coordinate->metadata['structured'])) {
        echo "  ✓ Used structured geocoding\n";
    }
} else {
    echo "❌ Could not geocode structured address\n";
}

echo "\n";

// Example 3: Comparing Simple vs Structured Geocoding
echo "Example 3: Comparing Simple vs Structured Geocoding\n";
echo str_repeat('-', 50) . "\n";

$fullAddress = "No. 24 Obi Okosi Street, Hill-Side Estate, Gwarimpa, Abuja, Nigeria";

echo "Testing address: {$fullAddress}\n\n";

// Simple geocoding
echo "Simple Geocoding:\n";
$simpleResult = Distance::geocode($fullAddress);
if ($simpleResult) {
    echo "  Accuracy: " . ($simpleResult->accuracy * 100) . "%\n";
    echo "  Formatted: {$simpleResult->formattedAddress}\n";
}

// Structured geocoding
echo "\nStructured Geocoding:\n";
$structuredResult = Distance::geocodeStructured($structuredAddress);
if ($structuredResult) {
    echo "  Accuracy: " . ($structuredResult->accuracy * 100) . "%\n";
    echo "  Formatted: {$structuredResult->formattedAddress}\n";
}

if ($simpleResult && $structuredResult) {
    $improvement = ($structuredResult->accuracy - $simpleResult->accuracy) * 100;
    if ($improvement > 0) {
        echo "\n✓ Structured geocoding improved accuracy by {$improvement}%\n";
    }
}

echo "\n";

// Example 4: Using Accuracy Scores for Decision Making
echo "Example 4: Using Accuracy Scores for Decision Making\n";
echo str_repeat('-', 50) . "\n";

$testAddresses = [
    "Abuja, Nigeria",
    "Gwarimpa, Abuja, Nigeria",
    "Obi Okosi Street, Gwarimpa, Abuja, Nigeria",
];

foreach ($testAddresses as $testAddress) {
    $result = Distance::geocode($testAddress);
    
    if ($result) {
        echo "Address: {$testAddress}\n";
        echo "Accuracy: " . ($result->accuracy * 100) . "%\n";
        
        if ($result->isHighAccuracy()) {
            echo "Action: ✓ Use coordinates directly\n";
        } elseif ($result->isLowAccuracy()) {
            echo "Action: ⚠ Prompt user to verify or use structured geocoding\n";
        } else {
            echo "Action: ~ Use with caution, consider verification\n";
        }
        echo "\n";
    }
}

// Example 5: Creating StructuredAddress from Array
echo "Example 5: Creating StructuredAddress from Array\n";
echo str_repeat('-', 50) . "\n";

$addressData = [
    'house_number' => '123',
    'street' => 'Admiralty Way',
    'suburb' => 'Lekki Phase 1',
    'city' => 'Lagos',
    'state' => 'Lagos State',
    'country' => 'Nigeria',
];

$address = StructuredAddress::fromArray($addressData);
echo "Created from array:\n";
echo "  String: {$address->toString()}\n";
echo "  Quality: " . ($address->getQualityScore() * 100) . "%\n";
echo "  Valid: " . ($address->isValid() ? 'Yes' : 'No') . "\n";

echo "\n=== Examples Complete ===\n";

