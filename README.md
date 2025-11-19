# Laravel Distance Calculator

A comprehensive Laravel package for calculating distances between locations with built-in geocoding support. Perfect for logistics, ride-sharing, delivery, and location-based applications.

## Features

- 🌍 **Multiple Geocoding Providers**: Nominatim (OpenStreetMap), Google Maps, Mapbox, and OpenCage
- 📏 **Accurate Distance Calculations**: Haversine and Vincenty formulas
- 🔄 **Flexible Input**: Accept addresses, coordinates, or coordinate strings
- 📦 **Multiple Units**: Kilometers, miles, meters, and feet
- 💾 **Built-in Caching**: Reduce API calls and improve performance
- 🎯 **Bearing & Direction**: Calculate bearing and compass direction between locations
- 🔌 **Easy Integration**: Laravel service provider and facade support

## Requirements

- PHP 8.2, 8.3, or 8.4
- Laravel 10.x, 11.x, or 12.x

## Installation

Install the package via Composer:

```bash
composer require abdullmng/laravel-distance
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=distance-config
```

## Configuration

Configure your preferred geocoding provider in `.env`:

```env
# Default provider (nominatim, google, mapbox, opencage)
GEOCODING_PROVIDER=nominatim

# Google Maps API Key (if using Google)
GOOGLE_MAPS_API_KEY=your-api-key

# Mapbox API Key (if using Mapbox)
MAPBOX_API_KEY=your-api-key

# OpenCage API Key (if using OpenCage)
OPENCAGE_API_KEY=your-api-key

# Nominatim settings (if using Nominatim)
NOMINATIM_URL=https://nominatim.openstreetmap.org
NOMINATIM_USER_AGENT=YourAppName/1.0

# Default distance unit (kilometers, miles, meters, feet)
DISTANCE_UNIT=kilometers

# Cache settings
GEOCODING_CACHE_ENABLED=true
GEOCODING_CACHE_DURATION=1440
```

## Usage

### Basic Distance Calculation

```php
use Abdullmng\Distance\Facades\Distance;

// Calculate distance between two addresses
$result = Distance::between(
    'New York, NY',
    'Los Angeles, CA'
);

echo $result->distance; // Distance in default unit (km)
echo $result->inMiles(); // Convert to miles
echo $result->inMeters(); // Convert to meters
```

### Using Coordinates

```php
use Abdullmng\Distance\DTOs\Coordinate;

// Using Coordinate objects
$from = new Coordinate(40.7128, -74.0060); // New York
$to = new Coordinate(34.0522, -118.2437); // Los Angeles

$result = Distance::between($from, $to);
```

### Using Coordinate Strings

```php
// Using coordinate strings
$result = Distance::between(
    '40.7128,-74.0060',
    '34.0522,-118.2437'
);
```

### Specify Unit

```php
// Calculate in miles
$result = Distance::between('New York, NY', 'Los Angeles, CA', 'miles');

// Or use the unit() method
$result = Distance::unit('miles')->between('New York, NY', 'Los Angeles, CA');
```

### Geocoding

```php
// Geocode an address
$coordinate = Distance::geocode('1600 Amphitheatre Parkway, Mountain View, CA');
echo $coordinate->latitude;
echo $coordinate->longitude;
echo $coordinate->formattedAddress;

// Reverse geocode
$address = Distance::reverse(37.4224764, -122.0842499);
echo $address; // "1600 Amphitheatre Parkway, Mountain View, CA..."
```

### Bearing and Direction

```php
// Get bearing (0-360 degrees)
$bearing = Distance::bearing('New York, NY', 'Los Angeles, CA');
echo $bearing; // e.g., 245.5

// Get compass direction
$direction = Distance::direction('New York, NY', 'Los Angeles, CA');
echo $direction; // e.g., "SW"
```

### Advanced: Vincenty Formula

For more accurate calculations (especially over long distances):

```php
$result = Distance::betweenVincenty('New York, NY', 'Los Angeles, CA');
```

### Working with Results

```php
$result = Distance::between('New York, NY', 'Los Angeles, CA');

// Access properties
echo $result->distance; // Distance in configured unit
echo $result->unit; // Unit used
echo $result->from->latitude; // Starting coordinate
echo $result->to->longitude; // Ending coordinate

// Convert to different units
echo $result->inKilometers();
echo $result->inMiles();
echo $result->inMeters();
echo $result->inFeet();

// Get as array
$array = $result->toArray();
```

## Geocoding Providers

### Nominatim (OpenStreetMap) - Free

No API key required. Great for development and low-volume applications.

```env
GEOCODING_PROVIDER=nominatim
NOMINATIM_USER_AGENT=YourAppName/1.0
```

### Google Maps - Paid

Highly accurate with extensive coverage.

```env
GEOCODING_PROVIDER=google
GOOGLE_MAPS_API_KEY=your-api-key
```

### Mapbox - Freemium

Good balance of accuracy and pricing.

```env
GEOCODING_PROVIDER=mapbox
MAPBOX_API_KEY=your-api-key
```

### OpenCage - Freemium

Aggregates multiple data sources.

```env
GEOCODING_PROVIDER=opencage
OPENCAGE_API_KEY=your-api-key
```

## Use Cases

### Logistics & Delivery Apps

```php
// Calculate delivery distance
$warehouse = new Coordinate(40.7128, -74.0060);
$customer = '123 Main St, Brooklyn, NY';

$result = Distance::between($warehouse, $customer, 'kilometers');

if ($result->distance > 50) {
    // Out of delivery range
}
```

### Ride-Sharing Apps

```php
// Find distance between driver and passenger
$driver = '40.7580,-73.9855';
$passenger = '40.7489,-73.9680';

$result = Distance::between($driver, $passenger, 'miles');
$eta = $result->distance / 30; // Assuming 30 mph average speed
```

### Store Locator

```php
$userLocation = Distance::geocode($userAddress);
$stores = Store::all();

foreach ($stores as $store) {
    $storeCoord = new Coordinate($store->latitude, $store->longitude);
    $distance = Distance::between($userLocation, $storeCoord, 'miles');
    $store->distance = $distance->distance;
}

$nearestStores = $stores->sortBy('distance')->take(5);
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for more information.

## Credits

- [Muhammad Abdullahi](https://github.com/abdullmng)

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/abdullmng/laravel-distance).


