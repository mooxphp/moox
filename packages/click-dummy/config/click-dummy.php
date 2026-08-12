<?php

declare(strict_types=1);

return [
    'enabled' => env('CLICK_DUMMY_ENABLED', true),

    'route_prefix' => env('CLICK_DUMMY_ROUTE_PREFIX', 'clickdummy'),

    /*
    |--------------------------------------------------------------------------
    | Storage root
    |--------------------------------------------------------------------------
    |
    | Absolute filesystem path containing clickdummy HTML and sibling assets.
    | Defaults to the host application's storage/clickdummy directory.
    |
    */
    'path' => env('CLICK_DUMMY_PATH', storage_path('clickdummy')),

    /*
    |--------------------------------------------------------------------------
    | Route middleware
    |--------------------------------------------------------------------------
    |
    | Explicitly protect the clickdummy routes. Keep `web` plus the
    | frontend-auth alias so access stays gated even if the global web-group
    | push from moox/frontend-auth is later disabled.
    |
    */
    'middleware' => ['web', 'moox.frontend-auth'],

    'allowed_extensions' => [
        'html',
        'htm',
        'css',
        'js',
        'mjs',
        'map',
        'json',
        'svg',
        'png',
        'jpg',
        'jpeg',
        'gif',
        'webp',
        'ico',
        'woff',
        'woff2',
        'ttf',
        'eot',
        'txt',
        'pdf',
        'mp4',
        'webm',
    ],
];
