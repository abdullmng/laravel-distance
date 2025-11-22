<?php

namespace Abdullmng\Distance\Providers;

use Abdullmng\Distance\Contracts\RoutingInterface;
use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\RouteResult;
use Abdullmng\Distance\Exceptions\RoutingException;
use Abdullmng\Distance\Traits\CacheableRouting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class MapboxRouter implements RoutingInterface
{
    use CacheableRouting;

    private Client $client;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (empty($config['api_key'])) {
            throw RoutingException::noApiKey();
        }

        // Ensure base_uri ends with / for proper path resolution
        $baseUri = rtrim($config['url'] ?? 'https://api.mapbox.com/directions/v5/mapbox', '/') . '/';

        $this->client = new Client([
            'base_uri' => $baseUri,
            'timeout' => $config['timeout'] ?? 10,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function route(Coordinate $from, Coordinate $to, array $options = []): ?RouteResult
    {
        $cacheKey = $this->getCacheKey('route', "{$from->longitude},{$from->latitude}:{$to->longitude},{$to->latitude}");

        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $profile = $options['mode'] ?? 'driving';
            
            // Mapbox profiles: driving-traffic, driving, walking, cycling
            $mapboxProfile = match ($profile) {
                'driving', 'car' => 'driving',
                'cycling', 'bike', 'bicycle' => 'cycling',
                'walking', 'foot' => 'walking',
                default => 'driving',
            };

            // Mapbox format: /{profile}/{lon},{lat};{lon},{lat}
            $path = "{$mapboxProfile}/{$from->longitude},{$from->latitude};{$to->longitude},{$to->latitude}";

            $response = $this->client->get($path, [
                'query' => [
                    'access_token' => $this->config['api_key'],
                    'geometries' => 'polyline',
                    'steps' => 'true',
                    'overview' => 'full',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['routes'])) {
                throw RoutingException::noRouteFound();
            }

            $route = $data['routes'][0];
            $unit = $this->config['unit'] ?? 'kilometers';

            // Mapbox returns distance in meters
            $distanceInMeters = $route['distance'];
            $distance = $this->convertDistance($distanceInMeters, 'meters', $unit);

            $steps = [];
            if (!empty($route['legs'][0]['steps'])) {
                foreach ($route['legs'][0]['steps'] as $step) {
                    $steps[] = [
                        'distance' => $step['distance'],
                        'duration' => $step['duration'],
                        'instruction' => $step['maneuver']['instruction'] ?? '',
                        'name' => $step['name'] ?? '',
                    ];
                }
            }

            $routeResult = new RouteResult(
                from: $from,
                to: $to,
                distance: $distance,
                unit: $unit,
                duration: $route['duration'], // in seconds
                steps: $steps,
                polyline: $route['geometry'] ?? null,
                summary: "Route via Mapbox ({$mapboxProfile})",
                type: 'route'
            );

            if ($this->isCacheEnabled()) {
                Cache::put($cacheKey, $routeResult, $this->getCacheDuration());
            }

            return $routeResult;
        } catch (GuzzleException $e) {
            throw RoutingException::apiError($e->getMessage());
        }
    }

    /**
     * Convert distance from one unit to another.
     *
     * @param float $distance
     * @param string $fromUnit
     * @param string $toUnit
     * @return float
     */
    private function convertDistance(float $distance, string $fromUnit, string $toUnit): float
    {
        // First convert to meters
        $meters = match ($fromUnit) {
            'kilometers', 'km' => $distance * 1000,
            'miles', 'mi' => $distance * 1609.34,
            'meters', 'm' => $distance,
            'feet', 'ft' => $distance * 0.3048,
            default => $distance,
        };

        // Then convert to target unit
        return match ($toUnit) {
            'kilometers', 'km' => $meters / 1000,
            'miles', 'mi' => $meters / 1609.34,
            'meters', 'm' => $meters,
            'feet', 'ft' => $meters / 0.3048,
            default => $meters / 1000,
        };
    }
}

