# Route-Based Distance Calculation

This package supports both **straight-line** (as-the-crow-flies) and **route-based** (actual road network) distance calculations.

## Overview

### Straight-Line Distance
- Uses Haversine or Vincenty formulas
- Calculates the shortest distance between two points on Earth
- Fast and doesn't require API calls
- Good for: proximity searches, radius calculations, bird's-eye distance

### Route-Based Distance
- Uses actual road networks and routing APIs
- Calculates real driving/walking/cycling distances
- Includes travel time estimates
- Good for: logistics, ride-sharing, delivery estimation, navigation

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Routing Provider (osrm, mapbox, google)
ROUTING_PROVIDER=osrm

# Default routing mode (driving, walking, cycling)
ROUTING_MODE=driving

# API Keys (only needed for Mapbox and Google)
MAPBOX_API_KEY=your_mapbox_token_here
GOOGLE_MAPS_API_KEY=your_google_api_key_here

# OSRM URL (optional, defaults to public OSRM server)
OSRM_URL=https://router.project-osrm.org
```

### Routing Providers

#### 1. OSRM (Open Source Routing Machine)
- **Free** and open-source
- **No API key required**
- Uses OpenStreetMap data
- Public server available at `https://router.project-osrm.org`
- Supports: driving (car), cycling (bike), walking (foot)
- **Recommended for development and testing**

#### 2. Mapbox Directions API
- Requires API key (free tier available)
- High-quality routing data
- Supports: driving, walking, cycling
- Traffic-aware routing available
- [Get API key](https://account.mapbox.com/)

#### 3. Google Maps Directions API
- Requires API key (paid service with free tier)
- Most comprehensive routing data
- Supports: driving, walking, bicycling, transit
- Real-time traffic data
- [Get API key](https://console.cloud.google.com/)

## Usage

### Basic Route Calculation

```php
use Abdullmng\Distance\Facades\Distance;

// Calculate route distance
$route = Distance::route('Lagos, Nigeria', 'Abuja, Nigeria');

echo $route->distance; // Distance in default unit (km)
echo $route->duration; // Duration in seconds
echo $route->formattedDuration(); // e.g., "6h 30m"
```

### Comparing Straight-Line vs Route Distance

```php
// Straight-line distance
$straightLine = Distance::between('Lagos', 'Abuja');
echo "Straight-line: {$straightLine->inKilometers()} km\n";

// Route distance
$route = Distance::route('Lagos', 'Abuja');
echo "Route: {$route->inKilometers()} km\n";
echo "Duration: {$route->formattedDuration()}\n";
```

### Different Travel Modes

```php
// Driving (default)
$driving = Distance::route($from, $to, ['mode' => 'driving']);

// Walking
$walking = Distance::route($from, $to, ['mode' => 'walking']);

// Cycling
$cycling = Distance::route($from, $to, ['mode' => 'cycling']);

// Transit (Google Maps only)
$transit = Distance::route($from, $to, ['mode' => 'transit']);
```

### Using Coordinates

```php
use Abdullmng\Distance\DTOs\Coordinate;

$warehouse = new Coordinate(6.5244, 3.3792); // Lagos
$customer = new Coordinate(9.0765, 7.3986); // Abuja

$route = Distance::route($warehouse, $customer, ['mode' => 'driving']);
```

### Route Result Properties

```php
$route = Distance::route($from, $to);

// Distance
$route->distance;           // float - in configured unit
$route->unit;              // string - 'kilometers', 'miles', etc.
$route->inKilometers();    // float
$route->inMiles();         // float
$route->inMeters();        // float
$route->inFeet();          // float

// Duration
$route->duration;          // float - in seconds
$route->durationInMinutes(); // float - in minutes
$route->durationInHours(); // float - in hours
$route->formattedDuration(); // string - e.g., "2h 30m"

// Route Information
$route->summary;           // string - route summary
$route->polyline;          // string - encoded polyline
$route->steps;             // array - turn-by-turn directions
$route->type;              // string - 'route' or 'straight'

// Coordinates
$route->from;              // Coordinate object
$route->to;                // Coordinate object

// Type Checking
$route->isRoute();         // bool - true
$route->isStraightLine();  // bool - false
```

## Use Cases

### 1. Logistics & Delivery

```php
$depot = 'Warehouse, Lagos';
$deliveries = [
    'Customer A, Ikeja',
    'Customer B, Victoria Island',
    'Customer C, Lekki',
];

foreach ($deliveries as $delivery) {
    $route = Distance::route($depot, $delivery, ['mode' => 'driving']);
    
    echo "Distance: {$route->inKilometers()} km\n";
    echo "ETA: " . date('H:i', time() + $route->duration) . "\n";
}
```

### 2. Ride-Sharing Fare Calculation

```php
$route = Distance::route($pickup, $dropoff, ['mode' => 'driving']);

$baseFare = 500;
$perKmRate = 150;
$perMinuteRate = 10;

$fare = $baseFare 
    + ($route->inKilometers() * $perKmRate)
    + ($route->durationInMinutes() * $perMinuteRate);

echo "Estimated fare: ₦{$fare}\n";
```

### 3. Delivery Time Estimation

```php
$route = Distance::route($warehouse, $customer);

$preparationTime = 15 * 60; // 15 minutes in seconds
$eta = time() + $preparationTime + $route->duration;

echo "Estimated delivery: " . date('H:i', $eta) . "\n";
```

### 4. API Response

```php
$route = Distance::route($from, $to);

return response()->json($route->toArray());
```

## Performance & Caching

Route calculations are automatically cached to reduce API calls:

```php
// config/distance.php
'cache' => [
    'enabled' => true,
    'duration' => 1440, // 24 hours in minutes
],
```

## Limitations

### OSRM
- Public server has rate limits
- Consider self-hosting for production
- No real-time traffic data

### Mapbox
- Free tier: 100,000 requests/month
- Rate limits apply

### Google Maps
- Paid service (free tier available)
- Most expensive but most comprehensive

## Best Practices

1. **Use OSRM for development** - Free and no API key required
2. **Cache results** - Enable caching to reduce API calls
3. **Choose the right provider** - Consider cost, accuracy, and features
4. **Handle errors** - Routing APIs can fail or return no routes
5. **Use appropriate modes** - Match the mode to your use case

## Error Handling

```php
use Abdullmng\Distance\Exceptions\RoutingException;

try {
    $route = Distance::route($from, $to);
} catch (RoutingException $e) {
    // Handle routing errors
    echo "Routing error: " . $e->getMessage();
}
```

## Switching Between Providers

You can switch providers at runtime by changing the environment variable or config:

```php
// In .env
ROUTING_PROVIDER=mapbox

// Or programmatically (if needed)
config(['distance.default_routing_provider' => 'google']);
```

## Self-Hosting OSRM

For production use, consider self-hosting OSRM:

```bash
# Using Docker
docker run -t -i -p 5000:5000 osrm/osrm-backend osrm-routed --algorithm mld /data/nigeria-latest.osrm
```

Then update your `.env`:

```env
OSRM_URL=http://localhost:5000
```

## Further Reading

- [OSRM Documentation](http://project-osrm.org/)
- [Mapbox Directions API](https://docs.mapbox.com/api/navigation/directions/)
- [Google Maps Directions API](https://developers.google.com/maps/documentation/directions)

