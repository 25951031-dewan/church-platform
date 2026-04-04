<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plugins Path
    |--------------------------------------------------------------------------
    |
    | This is the path where plugins are stored.
    |
    */
    'plugins_path' => app_path('Plugins'),

    /*
    |--------------------------------------------------------------------------
    | Cache Enabled
    |--------------------------------------------------------------------------
    |
    | Enable caching of plugin metadata for better performance.
    |
    */
    'cache_enabled' => env('PLUGINS_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long to cache plugin metadata in seconds.
    |
    */
    'cache_ttl' => env('PLUGINS_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Auto Discover
    |--------------------------------------------------------------------------
    |
    | Automatically discover and load plugins on boot.
    |
    */
    'auto_discover' => env('PLUGINS_AUTO_DISCOVER', true),
];
