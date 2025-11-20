# Mapbox Geocoding Guide

## Overview

This package uses the Mapbox Geocoding API v5 for geocoding addresses to coordinates.

## API Endpoint

The Mapbox Geocoding API v5 endpoint format is:
```
https://api.mapbox.com/geocoding/v5/mapbox.places/{search_text}.json?access_token=YOUR_TOKEN
```

## Configuration

In your `.env` file:
```env
GEOCODING_PROVIDER=mapbox
MAPBOX_API_KEY=your_mapbox_access_token_here
```

## How It Works

1. The package takes your address string (e.g., "No.24 Obi Okosi Street, Gwarimpa, Abuja")
2. URL-encodes it using `rawurlencode()`
3. Appends it to the base URL: `https://api.mapbox.com/geocoding/v5/mapbox.places/`
4. Adds `.json` extension
5. Adds your access token as a query parameter

## Example Request

For the address: `No.24 Obi Okosi Street, Hill-Side Estate, Gwarimpa, Abuja`

The final URL will be:
```
https://api.mapbox.com/geocoding/v5/mapbox.places/No.24%20Obi%20Okosi%20Street%2C%20Hill-Side%20Estate%2C%20Gwarimpa%2C%20Abuja.json?access_token=YOUR_TOKEN&limit=1
```

## Troubleshooting

### 404 Not Found Error

If you're getting a 404 error, check:

1. **API Key**: Make sure your `MAPBOX_API_KEY` is valid
2. **URL Format**: The base URL should be `https://api.mapbox.com/geocoding/v5/mapbox.places`
3. **Address Format**: Try simplifying your address (e.g., just "Gwarimpa, Abuja, Nigeria")

### Testing Your API Key

You can test your Mapbox API key directly in your browser or with curl:

```bash
curl "https://api.mapbox.com/geocoding/v5/mapbox.places/Abuja.json?access_token=YOUR_MAPBOX_ACCESS_TOKEN"
```

### Common Issues

1. **Invalid API Key**: Check your Mapbox account dashboard
2. **Rate Limiting**: Mapbox has rate limits on free tier
3. **Address Not Found**: Try a more general address (city name instead of full street address)

## Address Format Tips

For best results with Mapbox geocoding:

1. **Include country**: "Gwarimpa, Abuja, Nigeria" works better than just "Gwarimpa"
2. **Use common names**: Use well-known landmarks or areas
3. **Simplify**: Remove apartment/unit numbers if getting no results
4. **Try variations**: "Abuja, Nigeria" vs "Abuja FCT Nigeria"

## Response Format

Successful response includes:
- `latitude`: Decimal latitude
- `longitude`: Decimal longitude  
- `formattedAddress`: The place name from Mapbox (from `place_name` field)

## API Documentation

For more details, see:
- [Mapbox Geocoding API v5 Documentation](https://docs.mapbox.com/api/search/geocoding-v5/)
- [Mapbox Geocoding API v6 Documentation](https://docs.mapbox.com/api/search/geocoding/) (newer version)

## Upgrading to v6

Note: This package currently uses Mapbox Geocoding API v5. The v6 API has a different endpoint structure:
```
https://api.mapbox.com/search/geocode/v6/forward?q={search_text}&access_token=YOUR_TOKEN
```

If you need v6 features, please open an issue on GitHub.

