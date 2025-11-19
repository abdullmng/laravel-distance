# Laravel Distance Package Structure

## Overview

This document provides an overview of the package structure and architecture.

## Directory Structure

```
laravel-distance/
├── config/
│   └── distance.php              # Package configuration
├── src/
│   ├── Contracts/
│   │   └── GeocoderInterface.php # Geocoder contract
│   ├── DTOs/
│   │   ├── Coordinate.php        # Coordinate data object
│   │   └── DistanceResult.php    # Distance result data object
│   ├── Exceptions/
│   │   └── GeocodingException.php # Custom exceptions
│   ├── Facades/
│   │   └── Distance.php          # Laravel facade
│   ├── Providers/
│   │   ├── NominatimGeocoder.php # Nominatim implementation
│   │   ├── GoogleMapsGeocoder.php # Google Maps implementation
│   │   ├── MapboxGeocoder.php    # Mapbox implementation
│   │   └── OpenCageGeocoder.php  # OpenCage implementation
│   ├── Services/
│   │   └── DistanceCalculator.php # Distance calculation service
│   ├── Distance.php              # Main service class
│   └── DistanceServiceProvider.php # Laravel service provider
├── tests/
│   └── Unit/
│       ├── CoordinateTest.php    # Coordinate tests
│       └── DistanceCalculatorTest.php # Calculator tests
├── examples/
│   └── basic-usage.php           # Usage examples
├── .env.example                  # Environment configuration example
├── .gitignore                    # Git ignore rules
├── CHANGELOG.md                  # Version history
├── CONTRIBUTING.md               # Contribution guidelines
├── LICENSE                       # MIT License
├── QUICKSTART.md                 # Quick start guide
├── README.md                     # Main documentation
├── composer.json                 # Composer configuration
└── phpunit.xml                   # PHPUnit configuration
```

## Architecture

### Core Components

#### 1. Geocoding Layer
- **GeocoderInterface**: Contract for all geocoding providers
- **Provider Implementations**: Nominatim, Google Maps, Mapbox, OpenCage
- **Features**: Address to coordinates, reverse geocoding, caching

#### 2. Distance Calculation Layer
- **DistanceCalculator**: Implements Haversine and Vincenty formulas
- **Features**: Multiple units, bearing calculation, compass direction

#### 3. Service Layer
- **Distance**: Main service orchestrating geocoding and calculations
- **Features**: Flexible input handling, unit conversion, result formatting

#### 4. Data Transfer Objects (DTOs)
- **Coordinate**: Represents geographic coordinates with validation
- **DistanceResult**: Contains calculation results with unit conversions

### Design Patterns

1. **Strategy Pattern**: Interchangeable geocoding providers
2. **Facade Pattern**: Simple API access via Laravel facade
3. **Service Provider Pattern**: Laravel integration
4. **DTO Pattern**: Type-safe data transfer

### Key Features

#### Geocoding Providers
- Multiple provider support with easy switching
- Built-in caching to reduce API calls
- Retry logic for failed requests
- Comprehensive error handling

#### Distance Calculation
- Haversine formula (fast, good accuracy)
- Vincenty formula (slower, better accuracy)
- Support for 4 units: km, mi, m, ft
- Bearing and direction calculation

#### Developer Experience
- Laravel facade for easy access
- Fluent API design
- Comprehensive documentation
- Example code included
- Full test coverage

## Configuration

The package uses a single configuration file (`config/distance.php`) with:
- Default provider selection
- Provider-specific settings
- Cache configuration
- Retry settings
- Default unit

## Testing

Tests are organized by component:
- **Unit Tests**: Test individual components in isolation
- **Coverage**: All core functionality is tested

Run tests with:
```bash
composer test
```

## Extension Points

### Adding New Geocoding Providers

1. Create a new class implementing `GeocoderInterface`
2. Add configuration in `config/distance.php`
3. Register in `DistanceServiceProvider`
4. Add tests

### Adding New Distance Formulas

1. Add method to `DistanceCalculator`
2. Add corresponding method to `Distance` service
3. Add tests

## Dependencies

- **PHP**: 8.2, 8.3, or 8.4
- **Laravel**: 10.x, 11.x, or 12.x
- **Guzzle**: ^7.0 (HTTP client)
- **PHPUnit**: ^10.0|^11.0 (dev)
- **Orchestra Testbench**: ^8.0|^9.0|^10.0 (dev)

## Performance Considerations

- **Caching**: Geocoding results are cached by default (24 hours)
- **API Calls**: Minimized through caching and retry logic
- **Calculation**: Haversine is faster, Vincenty is more accurate
- **Memory**: DTOs use readonly properties for immutability

## Security

- API keys stored in environment variables
- Input validation on coordinates
- Exception handling for API errors
- No sensitive data in cache keys

## Future Enhancements

Potential areas for expansion:
- Batch geocoding support
- Route distance (not just straight-line)
- Elevation data
- Additional geocoding providers
- Database query scopes for location-based queries
- GeoJSON support

