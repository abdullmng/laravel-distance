<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Geocoding Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default geocoding provider that will be used
    | to convert addresses to coordinates. Supported providers: "nominatim",
    | "google", "mapbox", "opencage"
    |
    */

    'default_provider' => env('GEOCODING_PROVIDER', 'nominatim'),

    /*
    |--------------------------------------------------------------------------
    | Geocoding Fallback Chain
    |--------------------------------------------------------------------------
    |
    | Enable fallback chain to try multiple geocoding providers in order.
    | If enabled, the system will try providers in the order specified until
    | a result with sufficient accuracy is found.
    |
    */

    'use_fallback_chain' => env('GEOCODING_USE_FALLBACK', false),

    'fallback_providers' => [
        'nominatim',  // Try free provider first
        'opencage',   // Then try OpenCage
        'mapbox',     // Then Mapbox
        // 'google',  // Uncomment to use Google as last resort (most expensive)
    ],

    'minimum_accuracy' => env('GEOCODING_MIN_ACCURACY', 0.5), // 0-1 scale

    /*
    |--------------------------------------------------------------------------
    | Local Coordinate Cache
    |--------------------------------------------------------------------------
    |
    | Define known accurate coordinates for frequently used addresses.
    | This acts as a first-level cache before hitting external APIs.
    | Format: 'address' => ['lat' => float, 'lon' => float, 'formatted' => string]
    |
    */

    'local_coordinates' => [
        // Example:
        // 'Main Warehouse Lagos' => ['lat' => 6.5244, 'lon' => 3.3792, 'formatted' => 'Main Warehouse, Lagos, Nigeria'],
        // 'Office Abuja' => ['lat' => 9.0765, 'lon' => 7.3986, 'formatted' => 'Head Office, Abuja, Nigeria'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Geocoding Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the settings for each geocoding provider.
    | Each provider may require different API keys and have different options.
    |
    */

    'providers' => [
        'nominatim' => [
            'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
            'user_agent' => env('NOMINATIM_USER_AGENT', 'LaravelDistancePackage/1.0'),
            'timeout' => 10,
        ],

        'google' => [
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
            'url' => 'https://maps.googleapis.com/maps/api/geocode/json',
            'timeout' => 10,
        ],

        'mapbox' => [
            'api_key' => env('MAPBOX_API_KEY'),
            'url' => 'https://api.mapbox.com/geocoding/v5/mapbox.places',
            'timeout' => 10,
        ],

        'opencage' => [
            'api_key' => env('OPENCAGE_API_KEY'),
            'url' => 'https://api.opencagedata.com/geocode/v1/json',
            'timeout' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Distance Unit
    |--------------------------------------------------------------------------
    |
    | The default unit for distance calculations. Supported units:
    | "kilometers" (km), "miles" (mi), "meters" (m), "feet" (ft)
    |
    */

    'unit' => env('DISTANCE_UNIT', 'kilometers'),

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Enable caching of geocoding results to reduce API calls and improve
    | performance. Cache duration is in minutes.
    |
    */

    'cache' => [
        'enabled' => env('GEOCODING_CACHE_ENABLED', true),
        'duration' => env('GEOCODING_CACHE_DURATION', 1440), // 24 hours
        'prefix' => 'geocoding',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed geocoding requests.
    |
    */

    'retry' => [
        'enabled' => true,
        'times' => 3,
        'sleep' => 100, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Routing Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default routing provider for route-based
    | distance calculations. Supported providers: "osrm", "mapbox", "google"
    | OSRM is free and doesn't require an API key.
    |
    */

    'default_routing_provider' => env('ROUTING_PROVIDER', 'osrm'),

    /*
    |--------------------------------------------------------------------------
    | Routing Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure routing providers for calculating route-based distances.
    | These use actual road networks instead of straight-line calculations.
    |
    */

    'routing_providers' => [
        'osrm' => [
            'url' => env('OSRM_URL', 'https://router.project-osrm.org'),
            'timeout' => 10,
        ],

        'mapbox' => [
            'api_key' => env('MAPBOX_API_KEY'),
            'url' => 'https://api.mapbox.com/directions/v5/mapbox',
            'timeout' => 10,
        ],

        'google' => [
            'api_key' => env('GOOGLE_MAPS_API_KEY'),
            'url' => 'https://maps.googleapis.com/maps/api/directions/',
            'timeout' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Routing Mode
    |--------------------------------------------------------------------------
    |
    | The default mode for route calculations. Supported modes:
    | "driving" (car), "walking" (foot), "cycling" (bike)
    |
    */

    'routing_mode' => env('ROUTING_MODE', 'driving'),
];
