<?php

namespace Abdullmng\Distance;

use Abdullmng\Distance\Contracts\GeocoderInterface;
use Abdullmng\Distance\Providers\GoogleMapsGeocoder;
use Abdullmng\Distance\Providers\MapboxGeocoder;
use Abdullmng\Distance\Providers\NominatimGeocoder;
use Abdullmng\Distance\Providers\OpenCageGeocoder;
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
            $provider = config('distance.default_provider', 'nominatim');
            $config = config("distance.providers.{$provider}", []);

            return match ($provider) {
                'google' => new GoogleMapsGeocoder($config),
                'mapbox' => new MapboxGeocoder($config),
                'opencage' => new OpenCageGeocoder($config),
                'nominatim' => new NominatimGeocoder($config),
                default => new NominatimGeocoder($config),
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
            $unit = config('distance.unit', 'kilometers');

            return new Distance($geocoder, $calculator, $unit);
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
}

