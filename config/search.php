<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "database", "meilisearch", "algolia", "collection"
    |
    | "database" - Uses SQL LIKE queries (no extra setup, works everywhere)
    | "meilisearch" - Fast, typo-tolerant search (requires Meilisearch server)
    | "algolia" - Cloud-hosted search (requires Algolia account)
    | "collection" - In-memory search (for testing only)
    |
    */

    'driver' => env('SEARCH_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Searchable Models
    |--------------------------------------------------------------------------
    |
    | Define which models are searchable and their searchable fields.
    |
    */

    'models' => [
        'sermons' => [
            'model' => \App\Plugins\Sermons\Models\Sermon::class,
            'fields' => ['title', 'description', 'speakerProfile.name'],
            'filters' => ['is_published', 'series_id'],
        ],
        'posts' => [
            'model' => \App\Plugins\Blog\Models\Post::class,
            'fields' => ['title', 'body', 'excerpt'],
            'filters' => ['status', 'category_id'],
        ],
        'events' => [
            'model' => \App\Plugins\Events\Models\Event::class,
            'fields' => ['title', 'description', 'location'],
            'filters' => ['is_active'],
        ],
        'groups' => [
            'model' => \App\Plugins\Groups\Models\Group::class,
            'fields' => ['name', 'description'],
            'filters' => ['is_public', 'category'],
        ],
        'users' => [
            'model' => \App\Models\User::class,
            'fields' => ['name', 'email'],
            'filters' => ['is_active'],
        ],
        'prayers' => [
            'model' => \App\Plugins\Prayer\Models\PrayerRequest::class,
            'fields' => ['subject', 'request', 'name'],
            'filters' => ['status', 'is_public'],
        ],
        'marketplace' => [
            'model' => \App\Plugins\Marketplace\Models\Listing::class,
            'fields' => ['title', 'description'],
            'filters' => ['status', 'category_id', 'condition'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        'min_query_length' => 2,
        'max_results' => 100,
        'highlight' => true,
        'typo_tolerance' => true,
    ],
];
