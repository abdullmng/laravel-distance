# Quick Start Guide

Get started with Laravel Distance in 5 minutes!

## Requirements

- PHP 8.2, 8.3, or 8.4
- Laravel 10.x, 11.x, or 12.x

## Installation

```bash
composer require abdullmng/laravel-distance
```

## Basic Setup

### 1. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=distance-config
```

### 2. Configure Environment

Add to your `.env` file:

```env
# Use Nominatim (free, no API key required)
GEOCODING_PROVIDER=nominatim
NOMINATIM_USER_AGENT=MyApp/1.0

# Or use Google Maps (requires API key)
# GEOCODING_PROVIDER=google
# GOOGLE_MAPS_API_KEY=your-api-key

# Set default unit
DISTANCE_UNIT=kilometers
```

## Your First Distance Calculation

### Example 1: Calculate Distance Between Cities

```php
use Abdullmng\Distance\Facades\Distance;

$result = Distance::between('New York, NY', 'Los Angeles, CA');

echo "Distance: {$result->distance} km";
// Output: Distance: 3944.42 km

echo "In miles: {$result->inMiles()} mi";
// Output: In miles: 2451.03 mi
```

### Example 2: Using Coordinates

```php
use Abdullmng\Distance\DTOs\Coordinate;

$warehouse = new Coordinate(40.7128, -74.0060);
$customer = new Coordinate(40.7580, -73.9855);

$result = Distance::between($warehouse, $customer, 'kilometers');

if ($result->distance < 10) {
    echo "Free delivery available!";
}
```

### Example 3: Geocode an Address

```php
$coordinate = Distance::geocode('1600 Amphitheatre Parkway, Mountain View, CA');

echo "Latitude: {$coordinate->latitude}";
echo "Longitude: {$coordinate->longitude}";
```

### Example 4: Find Direction

```php
$direction = Distance::direction('New York, NY', 'Los Angeles, CA');
echo "Head {$direction}"; // Output: Head W
```

## Common Use Cases

### Delivery Range Check

```php
function canDeliver(string $warehouse, string $customer): bool
{
    $result = Distance::between($warehouse, $customer, 'kilometers');
    return $result->distance <= 50; // 50km max delivery range
}
```

### Calculate Shipping Cost

```php
function calculateShipping(string $from, string $to): float
{
    $result = Distance::between($from, $to, 'kilometers');
    $baseRate = 5.00;
    $perKm = 0.50;
    
    return $baseRate + ($result->distance * $perKm);
}
```

### Find Nearest Store

```php
$userLocation = Distance::geocode($userAddress);
$stores = Store::all();

foreach ($stores as $store) {
    $storeCoord = new Coordinate($store->lat, $store->lng);
    $result = Distance::between($userLocation, $storeCoord);
    $store->distance = $result->distance;
}

$nearest = $stores->sortBy('distance')->first();
```

## Available Methods

| Method | Description |
|--------|-------------|
| `between($from, $to, $unit)` | Calculate distance between two locations |
| `geocode($address)` | Convert address to coordinates |
| `reverse($lat, $lng)` | Convert coordinates to address |
| `bearing($from, $to)` | Get bearing in degrees (0-360) |
| `direction($from, $to)` | Get compass direction (N, NE, E, etc.) |
| `unit($unit)` | Set default unit for calculations |

## Supported Units

- `kilometers` or `km`
- `miles` or `mi`
- `meters` or `m`
- `feet` or `ft`

## Geocoding Providers

| Provider | API Key Required | Free Tier | Best For |
|----------|------------------|-----------|----------|
| Nominatim | No | Yes | Development, low volume |
| Google Maps | Yes | Limited | High accuracy, production |
| Mapbox | Yes | Yes | Good balance |
| OpenCage | Yes | Yes | Multiple data sources |

## Next Steps

- Read the full [README.md](README.md) for detailed documentation
- Check out [examples/basic-usage.php](examples/basic-usage.php) for more examples
- See [CONTRIBUTING.md](CONTRIBUTING.md) to contribute

## Need Help?

- [GitHub Issues](https://github.com/abdullmng/laravel-distance/issues)
- [Documentation](README.md)

Happy coding! 🚀

