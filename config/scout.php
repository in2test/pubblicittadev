<?php

declare(strict_types=1);

return [
    'driver' => env('SCOUT_DRIVER', 'database'),

    'database' => [
        'driver' => env('SCOUT_DATABASE_DRIVER', 'mysql'),
        'connection' => env('SCOUT_DATABASE_CONNECTION'),
        'indexing_batch_size' => env('SCOUT_DATABASE_INDEXING_BATCH_SIZE', 500),
    ],

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', ''),
        'key' => env('MEILISEARCH_SECRET', ''),
    ],

    'types' => [
        // 'App\Models\Product' => ['driver' => 'database'],
    ],
];
