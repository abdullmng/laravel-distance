# Changelog

All notable changes to `laravel-distance` will be documented in this file.

## [1.2.1] - 2025-11-22

### Fixed
- **Complete Structured Geocoding Implementation** - All geocoding providers now properly implement `geocodeStructured()`
  - Fixed `OpenCageGeocoder` to extend `AbstractGeocoder` and implement structured geocoding
  - Fixed `MapboxGeocoder` to extend `AbstractGeocoder` and implement structured geocoding
  - Fixed `GoogleMapsGeocoder` to extend `AbstractGeocoder` and implement structured geocoding
  - Added provider-specific accuracy scoring algorithms for each geocoder
  - Google Maps now uses native components API for structured geocoding (best accuracy)
  - OpenCage and Mapbox build formatted queries from structured addresses
  - All providers include accuracy boost for structured addresses (15-20%)

## [1.2.0] - 2025-11-22

### Added - Geocoding Accuracy Improvements
- **Structured Geocoding** - Break addresses into components for better accuracy
  - New `StructuredAddress` DTO with house number, street, city, etc.
  - `geocodeStructured()` method on Distance facade
  - Improved accuracy especially for addresses in developing countries
- **Geocoding Quality Scoring** - All geocoding results now include accuracy scores (0-1)
  - `accuracy` property on Coordinate DTO
  - `isHighAccuracy()` and `isLowAccuracy()` helper methods
  - `source` property to track which provider was used
  - `metadata` property for additional provider-specific data
- **Fallback Chain** - Automatically try multiple geocoding providers
  - New `FallbackGeocoder` provider
  - Configurable provider order and minimum accuracy threshold
  - Stops at first high-quality result or returns best available
- **Local Coordinate Cache** - Cache frequently used addresses
  - New `LocalCoordinateCache` provider
  - Configure known coordinates in config file
  - 100% accuracy for cached addresses
  - Zero API calls for cached locations
- **Geocoding Accuracy Helpers**
  - New `CalculatesGeocodingAccuracy` trait
  - New `AbstractGeocoder` base class for providers
  - Quality scoring based on result type and address matching

### Configuration
- Added `use_fallback_chain` config option
- Added `fallback_providers` array configuration
- Added `minimum_accuracy` threshold setting
- Added `local_coordinates` cache configuration
- New environment variables: `GEOCODING_USE_FALLBACK`, `GEOCODING_MIN_ACCURACY`

### Documentation
- Added comprehensive [GEOCODING_ACCURACY.md](GEOCODING_ACCURACY.md) guide
- Added [examples/geocoding-accuracy.php](examples/geocoding-accuracy.php) with practical examples
- Updated README with structured geocoding examples
- Updated README with fallback chain and local cache documentation

### Improved
- All geocoding providers now support structured geocoding
- Enhanced Nominatim geocoder with quality scoring
- Better handling of addresses in developing countries
- Maintained 100% backward compatibility

## [1.1.0] - 2025-11-22

### Added
- **Route-based distance calculation** - Calculate actual driving/walking/cycling distances using road networks
- Support for multiple routing providers:
  - OSRM (Open Source Routing Machine) - Free, no API key required
  - Mapbox Directions API - Freemium
  - Google Maps Directions API - Paid
- Travel time estimation for routes
- Multiple travel modes: driving, walking, cycling, transit (Google only)
- `RouteResult` DTO with distance, duration, steps, and polyline
- `route()` method on Distance facade for route-based calculations
- Automatic caching for routing results
- Turn-by-turn directions in route results
- Formatted duration output (e.g., "2h 30m")
- Route type checking (`isRoute()`, `isStraightLine()`)

### Documentation
- Added comprehensive [ROUTING.md](ROUTING.md) guide
- Added route-based examples in [examples/route-distance.php](examples/route-distance.php)
- Updated README with routing usage examples
- Added routing configuration options

### Configuration
- Added `default_routing_provider` config option
- Added `routing_providers` configuration section
- Added `routing_mode` default setting
- Added environment variables: `ROUTING_PROVIDER`, `ROUTING_MODE`, `OSRM_URL`

### Improved
- Maintained 100% backward compatibility - all existing methods work unchanged
- Straight-line distance calculations remain available via `between()` and `betweenVincenty()`

## [1.0.0] - 2025-11-19

### Added
- Initial release
- Support for PHP 8.2, 8.3, and 8.4
- Support for Laravel 10.x, 11.x, and 12.x
- Support for multiple geocoding providers:
  - Nominatim (OpenStreetMap) - Free
  - Google Maps - Paid
  - Mapbox - Freemium
  - OpenCage - Freemium
- Distance calculation using Haversine formula
- More accurate distance calculation using Vincenty formula
- Support for multiple distance units (kilometers, miles, meters, feet)
- Bearing and compass direction calculation
- Built-in caching for geocoding results
- Flexible input: addresses, coordinates, or coordinate strings
- Laravel service provider and facade support
- Comprehensive configuration options
- Exception handling for geocoding errors
- Reverse geocoding support

### Features
- Calculate distance between two locations
- Geocode addresses to coordinates
- Reverse geocode coordinates to addresses
- Get bearing between two locations
- Get compass direction between two locations
- Convert between different distance units
- Cache geocoding results to reduce API calls
- Retry failed requests automatically

### Documentation
- Comprehensive README with usage examples
- Example file with common use cases
- Configuration file with detailed comments
- Environment variable examples

