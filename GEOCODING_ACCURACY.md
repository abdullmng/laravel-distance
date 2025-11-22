# Geocoding Accuracy Guide

This guide explains how to improve geocoding accuracy in the Laravel Distance package, especially for addresses in developing countries where free geocoding providers may struggle.

## Table of Contents

- [The Problem](#the-problem)
- [Solutions](#solutions)
  - [1. Structured Geocoding](#1-structured-geocoding)
  - [2. Fallback Chain](#2-fallback-chain)
  - [3. Local Coordinate Cache](#3-local-coordinate-cache)
  - [4. Quality Scoring](#4-quality-scoring)
- [Configuration](#configuration)
- [Examples](#examples)

## The Problem

Free geocoding providers (Nominatim, OpenCage) often:

1. **Simplify addresses** - "No. 24 Obi Okosi Street, Gwarimpa, Abuja" → "Gwarimpa, Abuja"
2. **Can't find specific addresses** - Especially in developing countries
3. **Return approximate locations** - May be off by hundreds of meters

**Example:**
```php
// Input
$address = "No. 24 Obi Okosi Street, Hill-Side Estate, Gwarimpa, Abuja, Nigeria";

// What you get from free providers
$result = Distance::geocode($address);
// Returns: "Gwarimpa, Abuja, Nigeria" (loses street and house number)
```

## Solutions

### 1. Structured Geocoding

Instead of passing the full address as one string, break it into components:

```php
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Facades\Distance;

$address = new StructuredAddress(
    houseNumber: '24',
    street: 'Obi Okosi Street',
    neighbourhood: 'Hill-Side Estate',
    suburb: 'Gwarimpa',
    city: 'Abuja',
    country: 'Nigeria'
);

$coordinate = Distance::geocodeStructured($address);

if ($coordinate) {
    echo "Latitude: {$coordinate->latitude}\n";
    echo "Longitude: {$coordinate->longitude}\n";
    echo "Accuracy: " . ($coordinate->accuracy * 100) . "%\n";
    echo "Source: {$coordinate->source}\n";
}
```

**Benefits:**
- Geocoders can better understand the address structure
- Higher accuracy scores
- Better matching for specific locations

### 2. Fallback Chain

Automatically try multiple geocoding providers until you get a high-quality result:

**Configuration** (`config/distance.php`):
```php
'use_fallback_chain' => true,

'fallback_providers' => [
    'nominatim',  // Try free provider first
    'opencage',   // Then try OpenCage
    'mapbox',     // Then Mapbox
    'google',     // Last resort (most expensive but best)
],

'minimum_accuracy' => 0.7, // Only accept results with 70%+ accuracy
```

**Usage:**
```php
// Automatically tries providers in order until getting good result
$coordinate = Distance::geocode('No. 24 Obi Okosi Street, Gwarimpa, Abuja, Nigeria');

if ($coordinate->isHighAccuracy()) {
    echo "Got high-quality result from: {$coordinate->source}\n";
} else {
    echo "Warning: Low accuracy result ({$coordinate->accuracy})\n";
}
```

### 3. Local Coordinate Cache

For frequently used addresses (warehouses, offices, delivery points), maintain a local database:

**Configuration** (`config/distance.php`):
```php
'local_coordinates' => [
    'Main Warehouse Lagos' => [
        'lat' => 6.5244,
        'lon' => 3.3792,
        'formatted' => 'Main Warehouse, Apapa, Lagos, Nigeria'
    ],
    'Office Abuja' => [
        'lat' => 9.0765,
        'lon' => 7.3986,
        'formatted' => 'Head Office, Central Business District, Abuja, Nigeria'
    ],
    'No. 24 Obi Okosi Street, Gwarimpa, Abuja' => [
        'lat' => 9.0876,
        'lon' => 7.4123,
        'formatted' => 'No. 24 Obi Okosi Street, Hill-Side Estate, Gwarimpa, Abuja, Nigeria'
    ],
],
```

**Usage:**
```php
// Checks local cache first (instant, 100% accurate)
$coordinate = Distance::geocode('Main Warehouse Lagos');

echo "Accuracy: " . ($coordinate->accuracy * 100) . "%\n"; // 100%
echo "Source: {$coordinate->source}\n"; // local_cache
```

**Benefits:**
- Instant results (no API calls)
- 100% accuracy for known addresses
- Reduces API costs
- Falls back to external geocoders for unknown addresses

### 4. Quality Scoring

All geocoding results now include an accuracy score (0-1):

```php
$coordinate = Distance::geocode('Gwarimpa, Abuja, Nigeria');

// Check accuracy
if ($coordinate->isHighAccuracy()) {
    // accuracy >= 0.8
    echo "High quality result - safe to use\n";
} elseif ($coordinate->isLowAccuracy()) {
    // accuracy < 0.5
    echo "Low quality result - may be inaccurate\n";
    echo "Consider using structured geocoding or manual coordinates\n";
} else {
    // 0.5 <= accuracy < 0.8
    echo "Moderate quality result\n";
}

// Access raw accuracy score
echo "Accuracy: " . ($coordinate->accuracy * 100) . "%\n";

// See which provider gave this result
echo "Source: {$coordinate->source}\n";

// Access additional metadata
print_r($coordinate->metadata);
```

## Configuration

### Enable All Features

```php
// config/distance.php
return [
    // Use fallback chain
    'use_fallback_chain' => true,
    
    'fallback_providers' => [
        'nominatim',
        'opencage',
        'mapbox',
        // 'google', // Uncomment if you have API key
    ],
    
    'minimum_accuracy' => 0.6,
    
    // Local coordinate cache
    'local_coordinates' => [
        // Add your frequently used addresses here
    ],
];
```

### Environment Variables

```env
# Enable fallback chain
GEOCODING_USE_FALLBACK=true
GEOCODING_MIN_ACCURACY=0.6

# API Keys for providers
MAPBOX_API_KEY=your_mapbox_key
OPENCAGE_API_KEY=your_opencage_key
GOOGLE_MAPS_API_KEY=your_google_key
```

## Examples

### Example 1: Structured Geocoding for Nigerian Address

```php
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Facades\Distance;

$address = new StructuredAddress(
    houseNumber: '24',
    street: 'Obi Okosi Street',
    suburb: 'Gwarimpa',
    city: 'Abuja',
    country: 'Nigeria'
);

$coordinate = Distance::geocodeStructured($address);

if ($coordinate && $coordinate->isHighAccuracy()) {
    echo "Found accurate location!\n";
    echo "Coordinates: {$coordinate->latitude}, {$coordinate->longitude}\n";
} else {
    echo "Could not find accurate location\n";
    echo "Consider adding to local coordinate cache\n";
}
```

### Example 2: Using Fallback Chain with Quality Check

```php
// Will try multiple providers automatically
$coordinate = Distance::geocode('Plot 123, Admiralty Way, Lekki Phase 1, Lagos');

if ($coordinate) {
    echo "Provider: {$coordinate->source}\n";
    echo "Accuracy: " . ($coordinate->accuracy * 100) . "%\n";
    
    if ($coordinate->isLowAccuracy()) {
        // Prompt user to verify or provide more details
        echo "Warning: Location may not be accurate. Please verify.\n";
    }
}
```

### Example 3: Combining All Features

```php
// 1. Check local cache first (configured in config/distance.php)
// 2. If not in cache, try structured geocoding
// 3. If structured fails, try fallback chain
// 4. Check quality of result

$address = new StructuredAddress(
    street: 'Obi Okosi Street',
    suburb: 'Gwarimpa',
    city: 'Abuja',
    country: 'Nigeria'
);

$coordinate = Distance::geocodeStructured($address);

if ($coordinate) {
    if ($coordinate->source === 'local_cache') {
        echo "Using cached coordinate (100% accurate)\n";
    } elseif ($coordinate->isHighAccuracy()) {
        echo "Got high-quality result from {$coordinate->source}\n";
        // Optionally add to local cache for future use
    } else {
        echo "Got result but accuracy is low: " . ($coordinate->accuracy * 100) . "%\n";
        echo "Consider manually verifying this location\n";
    }
}
```

## Best Practices

1. **Use structured geocoding** for specific addresses
2. **Enable fallback chain** to try multiple providers
3. **Cache frequently used addresses** in local coordinate cache
4. **Always check accuracy scores** before using coordinates
5. **Prompt users to verify** low-accuracy results
6. **Start with free providers** (Nominatim, OSRM) and fall back to paid ones
7. **Include country in addresses** for better results

## Troubleshooting

### Problem: Still getting low accuracy

**Solution:**
1. Add the address to local coordinate cache with manually verified coordinates
2. Try Google Maps geocoder (most accurate but paid)
3. Ask users to provide coordinates directly

### Problem: Fallback chain is slow

**Solution:**
1. Reduce number of providers in fallback chain
2. Increase `minimum_accuracy` to stop earlier
3. Use local coordinate cache for common addresses

### Problem: Too many API calls

**Solution:**
1. Enable caching (enabled by default)
2. Use local coordinate cache for frequent addresses
3. Increase cache duration in config

