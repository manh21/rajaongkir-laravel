<?php

// config for Komodo/RajaOngkir
return [
    'api_key' => env('RAJAONGKIR_API_KEY'),
    'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
    'cost_cache_duration' => env('RAJAONGKIR_COST_CACHE_DURATION', 60), // in minutes
    'location_cache_duration' => env('RAJAONGKIR_LOCATION_CACHE_DURATION', 1440), // in minutes (1 day)

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | This package supports advanced cache features when using cache drivers
    | that support tagging (Redis, Memcached). For other cache drivers
    | (file, database, array), basic caching will be used without tagging.
    |
    | For best performance and cache management, consider using Redis:
    | - CACHE_DRIVER=redis in your .env file
    | - Install and configure Redis server
    |
    */
];
