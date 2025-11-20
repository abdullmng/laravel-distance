<?php

require __DIR__ . '/vendor/autoload.php';

use Abdullmng\Distance\Providers\MapboxGeocoder;

// Load environment variables if using vlucas/phpdotenv
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$config = [
    'api_key' => getenv('MAPBOX_API_KEY') ?: 'your-api-key-here',
    'url' => 'https://api.mapbox.com/geocoding/v5/mapbox.places',
    'timeout' => 10,
];

try {
    $geocoder = new MapboxGeocoder($config);
    
    $address = 'No.24 Obi Okosi Street, Hill-Side Estate, Gwarimpa, Abuja';
    echo "Geocoding address: {$address}\n\n";
    
    $coordinate = $geocoder->geocode($address);
    
    if ($coordinate) {
        echo "Success!\n";
        echo "Latitude: {$coordinate->latitude}\n";
        echo "Longitude: {$coordinate->longitude}\n";
        echo "Formatted Address: {$coordinate->formattedAddress}\n";
    } else {
        echo "No results found.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

