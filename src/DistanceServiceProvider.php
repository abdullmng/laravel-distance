<?php

namespace Abdullmng\Distance;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\Contracts\RoutingInterface;
use Abdullmng\Distance\Providers\GoogleMapsGeocoder;
use Abdullmng\Distance\Providers\GoogleMapsRouter;
use Abdullmng\Distance\Providers\MapboxGeocoder;
use Abdullmng\Distance\Providers\MapboxRouter;
use Abdullmng\Distance\Providers\NominatimGeocoder;
use Abdullmng\Distance\Providers\OpenCageGeocoder;
use Abdullmng\Distance\Providers\OsrmRouter;
use Abdullmng\Distance\Services\DistanceCalculator;
use Illuminate\Support\ServiceProvider;

class DistanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/distance.php',
            'distance'
        );

        // Register the geocoder based on configuration
        $this->app->singleton(GeocoderInterface::class, function ($app) {
            // Check if we should use fallback chain
            if (config('distance.use_fallback_chain', false)) {
                return $this->createFallbackGeocoder();
            }

            // Check if we should use local coordinate cache
            $localCoordinates = config('distance.local_coordinates', []);
            $geocoder = $this->createGeocoder(config('distance.default_provider', 'nominatim'));

            if (!empty($localCoordinates)) {
                return $this->createLocalCacheGeocoder($localCoordinates, $geocoder);
            }

            return $geocoder;
        });

        // Register the routing provider based on configuration
        $this->app->singleton(RoutingInterface::class, function ($app) {
            $provider = config('distance.default_routing_provider', 'osrm');
            $config = config("distance.routing_providers.{$provider}", []);
            $config['unit'] = config('distance.unit', 'kilometers');

            return match ($provider) {
                'google' => new GoogleMapsRouter($config),
                'mapbox' => new MapboxRouter($config),
                'osrm' => new OsrmRouter($config),
                default => new OsrmRouter($config),
            };
        });

        // Register the distance calculator
        $this->app->singleton(DistanceCalculator::class, function ($app) {
            return new DistanceCalculator();
        });

        // Register the main Distance service
        $this->app->singleton(Distance::class, function ($app) {
            $geocoder = $app->make(GeocoderInterface::class);
            $calculator = $app->make(DistanceCalculator::class);
            $router = $app->make(RoutingInterface::class);
            $unit = config('distance.unit', 'kilometers');

            return new Distance($geocoder, $calculator, $router, $unit);
        });

        // Alias for easier access
        $this->app->alias(Distance::class, 'distance');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/distance.php' => config_path('distance.php'),
            ], 'distance-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            GeocoderInterface::class,
            DistanceCalculator::class,
            Distance::class,
            'distance',
        ];
    }

    /**
     * Create a geocoder instance for the given provider.
     *
     * @param string $provider
     * @return GeocoderInterface
     */
    private function createGeocoder(string $provider): GeocoderInterface
    {
        $config = config("distance.providers.{$provider}", []);

        return match ($provider) {
            'google' => new GoogleMapsGeocoder($config),
            'mapbox' => new MapboxGeocoder($config),
            'opencage' => new OpenCageGeocoder($config),
            'nominatim' => new NominatimGeocoder($config),
            default => new NominatimGeocoder($config),
        };
    }

    /**
     * Create a fallback geocoder with multiple providers.
     *
     * @return GeocoderInterface
     */
    private function createFallbackGeocoder(): GeocoderInterface
    {
        $providerNames = config('distance.fallback_providers', ['nominatim']);
        $providers = [];

        foreach ($providerNames as $providerName) {
            try {
                $providers[] = $this->createGeocoder($providerName);
            } catch (\Exception $e) {
                // Skip providers that fail to initialize
                continue;
            }
        }

        $minimumAccuracy = config('distance.minimum_accuracy', 0.5);
        $fallbackGeocoder = new \Abdullmng\Distance\Providers\FallbackGeocoder($providers, $minimumAccuracy);

        // Wrap with local cache if configured
        $localCoordinates = config('distance.local_coordinates', []);
        if (!empty($localCoordinates)) {
            return $this->createLocalCacheGeocoder($localCoordinates, $fallbackGeocoder);
        }

        return $fallbackGeocoder;
    }

    /**
     * Create a local coordinate cache geocoder.
     *
     * @param array $localCoordinates
     * @param GeocoderInterface $fallbackGeocoder
     * @return GeocoderInterface
     */
    private function createLocalCacheGeocoder(array $localCoordinates, GeocoderInterface $fallbackGeocoder): GeocoderInterface
    {
        $coordinates = [];

        foreach ($localCoordinates as $address => $data) {
            $coordinates[$address] = new \Abdullmng\Distance\DTOs\Coordinate(
                latitude: $data['lat'],
                longitude: $data['lon'],
                formattedAddress: $data['formatted'] ?? $address,
                accuracy: 1.0,
                source: 'local_cache'
            );
        }

        return new \Abdullmng\Distance\Providers\LocalCoordinateCache($coordinates, $fallbackGeocoder);
    }
}
