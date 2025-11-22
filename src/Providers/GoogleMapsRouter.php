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

class GoogleMapsRouter implements RoutingInterface
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

        $this->client = new Client([
            'base_uri' => $config['url'] ?? 'https://maps.googleapis.com/maps/api/directions/',
            'timeout' => $config['timeout'] ?? 10,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function route(Coordinate $from, Coordinate $to, array $options = []): ?RouteResult
    {
        $cacheKey = $this->getCacheKey('route', "{$from->latitude},{$from->longitude}:{$to->latitude},{$to->longitude}");

        if ($this->isCacheEnabled() && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $mode = $options['mode'] ?? 'driving';
            
            // Google Maps modes: driving, walking, bicycling, transit
            $googleMode = match ($mode) {
                'driving', 'car' => 'driving',
                'cycling', 'bike', 'bicycle' => 'bicycling',
                'walking', 'foot' => 'walking',
                'transit' => 'transit',
                default => 'driving',
            };

            $response = $this->client->get('json', [
                'query' => [
                    'origin' => "{$from->latitude},{$from->longitude}",
                    'destination' => "{$to->latitude},{$to->longitude}",
                    'mode' => $googleMode,
                    'key' => $this->config['api_key'],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['status'] !== 'OK' || empty($data['routes'])) {
                throw RoutingException::noRouteFound();
            }

            $route = $data['routes'][0];
            $leg = $route['legs'][0];
            $unit = $this->config['unit'] ?? 'kilometers';

            // Google returns distance in meters
            $distanceInMeters = $leg['distance']['value'];
            $distance = $this->convertDistance($distanceInMeters, 'meters', $unit);

            $steps = [];
            if (!empty($leg['steps'])) {
                foreach ($leg['steps'] as $step) {
                    $steps[] = [
                        'distance' => $step['distance']['value'],
                        'duration' => $step['duration']['value'],
                        'instruction' => strip_tags($step['html_instructions'] ?? ''),
                        'name' => '',
                    ];
                }
            }

            $routeResult = new RouteResult(
                from: $from,
                to: $to,
                distance: $distance,
                unit: $unit,
                duration: $leg['duration']['value'], // in seconds
                steps: $steps,
                polyline: $route['overview_polyline']['points'] ?? null,
                summary: $leg['summary'] ?? "Route via Google Maps ({$googleMode})",
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

