# Quick Start: Improved Geocoding for Nigerian Addresses

This guide shows you how to quickly set up improved geocoding for addresses in Nigeria (or any developing country).

## Problem

Free geocoding providers often fail or return inaccurate results for specific addresses:

```php
// Input: "No. 24 Obi Okosi Street, Gwarimpa, Abuja, Nigeria"
// Output: "Gwarimpa, Abuja, Nigeria" ❌ (loses street and house number)
```

## Solution 1: Structured Geocoding (Recommended)

**Step 1:** Use structured addresses instead of strings

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

if ($coordinate->isHighAccuracy()) {
    // Use the coordinates
    echo "Lat: {$coordinate->latitude}, Lon: {$coordinate->longitude}\n";
} else {
    // Prompt user to verify or use fallback
    echo "Low accuracy - please verify location\n";
}
```

## Solution 2: Local Coordinate Cache (Best for Known Addresses)

**Step 1:** Add known addresses to `config/distance.php`

```php
'local_coordinates' => [
    'No. 24 Obi Okosi Street, Gwarimpa, Abuja' => [
        'lat' => 9.0876,
        'lon' => 7.4123,
        'formatted' => 'No. 24 Obi Okosi Street, Hill-Side Estate, Gwarimpa, Abuja, Nigeria'
    ],
    'Main Warehouse Lagos' => [
        'lat' => 6.5244,
        'lon' => 3.3792,
        'formatted' => 'Main Warehouse, Apapa, Lagos, Nigeria'
    ],
],
```

**Step 2:** Use normally - it checks cache first

```php
$coordinate = Distance::geocode('Main Warehouse Lagos');
// Instant result, 100% accurate, no API calls!
```

## Solution 3: Fallback Chain (Try Multiple Providers)

**Step 1:** Enable in `.env`

```env
GEOCODING_USE_FALLBACK=true
GEOCODING_MIN_ACCURACY=0.7

# Add API keys for providers you want to use
MAPBOX_API_KEY=your_key_here
OPENCAGE_API_KEY=your_key_here
```

**Step 2:** Configure providers in `config/distance.php`

```php
'use_fallback_chain' => true,

'fallback_providers' => [
    'nominatim',  // Free - try first
    'opencage',   // Freemium - try second
    'mapbox',     // Freemium - try third
    // 'google',  // Paid - uncomment if you have API key
],

'minimum_accuracy' => 0.7,
```

**Step 3:** Use normally - it tries providers automatically

```php
$coordinate = Distance::geocode('Gwarimpa, Abuja, Nigeria');
// Tries Nominatim first, then OpenCage, then Mapbox until getting good result
```

## Recommended Setup for Nigerian Addresses

**1. Enable all features in `config/distance.php`:**

```php
return [
    // Use fallback chain
    'use_fallback_chain' => true,
    
    'fallback_providers' => [
        'nominatim',
        'opencage',
        'mapbox',
    ],
    
    'minimum_accuracy' => 0.6,
    
    // Cache known addresses
    'local_coordinates' => [
        // Add your warehouses, offices, etc.
        'Warehouse Ikeja' => ['lat' => 6.5954, 'lon' => 3.3364, 'formatted' => 'Warehouse, Ikeja, Lagos'],
        'Office VI' => ['lat' => 6.4281, 'lon' => 3.4219, 'formatted' => 'Office, Victoria Island, Lagos'],
    ],
];
```

**2. In your code, use structured geocoding:**

```php
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Facades\Distance;

// For user-provided addresses
$address = new StructuredAddress(
    street: $request->street,
    suburb: $request->area,
    city: $request->city,
    state: $request->state,
    country: 'Nigeria'
);

$coordinate = Distance::geocodeStructured($address);

if ($coordinate) {
    if ($coordinate->isHighAccuracy()) {
        // Good to use
        $this->saveLocation($coordinate);
    } else {
        // Ask user to verify
        return response()->json([
            'coordinate' => $coordinate,
            'accuracy' => $coordinate->accuracy,
            'message' => 'Please verify this location on the map',
        ]);
    }
}
```

## Best Practices

1. **Always check accuracy scores** before using coordinates
2. **Use structured geocoding** for specific addresses
3. **Cache frequently used addresses** in local_coordinates
4. **Prompt users to verify** low-accuracy results
5. **Include country** in all addresses for better results

## Example: Complete Workflow

```php
use Abdullmng\Distance\DTOs\StructuredAddress;
use Abdullmng\Distance\Facades\Distance;

// 1. Check if address is in local cache (instant, 100% accurate)
$coordinate = Distance::geocode($fullAddress);

if ($coordinate && $coordinate->source === 'local_cache') {
    return $coordinate; // Perfect!
}

// 2. Try structured geocoding with fallback chain
$structured = new StructuredAddress(
    street: 'Obi Okosi Street',
    suburb: 'Gwarimpa',
    city: 'Abuja',
    country: 'Nigeria'
);

$coordinate = Distance::geocodeStructured($structured);

// 3. Check quality
if ($coordinate) {
    if ($coordinate->isHighAccuracy()) {
        // Great! Use it
        return $coordinate;
    } elseif ($coordinate->isLowAccuracy()) {
        // Prompt user to verify or provide coordinates manually
        return [
            'coordinate' => $coordinate,
            'needs_verification' => true,
            'accuracy' => $coordinate->accuracy * 100 . '%',
        ];
    } else {
        // Moderate quality - use with caution
        return [
            'coordinate' => $coordinate,
            'warning' => 'Location may not be exact',
        ];
    }
}

// 4. Geocoding failed - ask user for coordinates
return ['error' => 'Could not find location. Please provide coordinates or select on map.'];
```

## Need Help?

- See [GEOCODING_ACCURACY.md](GEOCODING_ACCURACY.md) for detailed guide
- See [examples/geocoding-accuracy.php](examples/geocoding-accuracy.php) for more examples
- Check [README.md](README.md) for full documentation

