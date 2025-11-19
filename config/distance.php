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
];

