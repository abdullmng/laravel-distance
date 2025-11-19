<?php

namespace Abdullmng\Distance\Exceptions;

use Exception;

class GeocodingException extends Exception
{
    public static function invalidAddress(string $address): self
    {
        return new self("Could not geocode address: {$address}");
    }

    public static function apiError(string $message): self
    {
        return new self("Geocoding API error: {$message}");
    }

    public static function noApiKey(): self
    {
        return new self("No geocoding API key configured. Please set the appropriate API key in your .env file.");
    }

    public static function invalidProvider(string $provider): self
    {
        return new self("Invalid geocoding provider: {$provider}. Supported providers: nominatim, google, mapbox, opencage");
    }

    public static function rateLimitExceeded(string $provider): self
    {
        return new self("Rate limit exceeded for {$provider}. Please try again later or upgrade your plan.");
    }

    public static function networkError(string $message): self
    {
        return new self("Network error during geocoding: {$message}");
    }
}
