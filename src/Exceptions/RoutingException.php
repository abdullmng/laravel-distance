<?php

namespace Abdullmng\Distance\Exceptions;

use Exception;

class RoutingException extends Exception
{
    public static function noRouteFound(): self
    {
        return new self('No route found between the specified locations.');
    }

    public static function apiError(string $message): self
    {
        return new self("Routing API error: {$message}");
    }

    public static function noApiKey(): self
    {
        return new self('Routing API key is required but not provided.');
    }

    public static function invalidMode(string $mode): self
    {
        return new self("Invalid routing mode: {$mode}. Supported modes: driving, walking, cycling.");
    }
}

