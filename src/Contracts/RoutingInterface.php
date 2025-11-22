<?php

namespace Abdullmng\Distance\Contracts;

use Abdullmng\Distance\DTOs\Coordinate;
use Abdullmng\Distance\DTOs\RouteResult;

interface RoutingInterface
{
    /**
     * Calculate route between two coordinates.
     *
     * @param Coordinate $from
     * @param Coordinate $to
     * @param array $options Additional options (e.g., mode: driving, walking, cycling)
     * @return RouteResult|null
     */
    public function route(Coordinate $from, Coordinate $to, array $options = []): ?RouteResult;
}

