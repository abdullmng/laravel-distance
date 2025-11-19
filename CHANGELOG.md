# Changelog

All notable changes to `laravel-distance` will be documented in this file.

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

