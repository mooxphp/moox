<?php

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core. Example:
|
| 'trans//core::core.all',
| loads from common.php
| outputs 'All'
|
*/

return [
    'enable-panel' => env('CONNECT_ENABLE_PANEL', true),

    'auth_fields' => [
        'bearer' => [
            'token' => env('CONNECT_AUTH_BEARER_TOKEN_KEY', 'token'),
        ],
        'basic' => [
            'username' => env('CONNECT_AUTH_BASIC_USERNAME_KEY', 'username'),
            'password' => env('CONNECT_AUTH_BASIC_PASSWORD_KEY', 'password'),
        ],
        'jwt' => [
            'access_token' => env('CONNECT_AUTH_JWT_ACCESS_TOKEN_KEY', 'access_token'),
        ],
    ],

    'notifications' => [
        'email' => env('MAIL_TO_ADDRESS', config('mail.to.address')),
    ],

    'binary_preview' => [
        'file_name_keys' => [
            'file_name',
            'filename',
            'FileName',
        ],
        'base64_keys' => [
            'body',
            'base64',
        ],
    ],

    'rate_limits' => [
        'global' => [
            'max_requests' => 1000,  // requests
            'window' => 60,          // seconds
        ],

        'per_endpoint' => [
            'default' => [
                'max_requests' => 100,
                'window' => 60,
            ],
            // Can be overridden per endpoint in endpoint config
        ],

        'per_job' => [
            'default' => [
                'max_requests' => 50,
                'window' => 60,
            ],
            // Can be overridden in job config
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue configuration
    |--------------------------------------------------------------------------
    |
    | Resolution order per setting (queue / tries / timeout):
    | 1. endpoint.options.queue / queue.name / queue.tries / queue.timeout
    | 2. connect.queues.endpoints.{id|name}
    | 3. connection.options.queue / queue.name / queue.tries / queue.timeout
    | 4. connect.queues.connections.{id|name}
    | 5. connect.jobs.{job_type}.*
    | 6. connect.queues.default / worker_tries / worker_timeout
    |
    */
    'queues' => [
        'worker' => env('CONNECT_QUEUE_WORKER', 'default,connect-detail'),
        'worker_tries' => (int) env('CONNECT_QUEUE_WORKER_TRIES', 5),
        'worker_timeout' => (int) env('CONNECT_QUEUE_WORKER_TIMEOUT', 180),
        'default' => env('CONNECT_QUEUE_DEFAULT', 'default'),

        'connections' => [
            // 1 => ['queue' => 'comwork', 'tries' => 5, 'timeout' => 180],
            // 'Comwork' => ['queue' => 'comwork'],
        ],

        'endpoints' => [
            // 6 => ['queue' => 'articles-de'],
            // 'Article DE' => ['queue' => 'articles-de'],
        ],
    ],

    'jobs' => [
        'detail_item' => [
            'queue' => env('CONNECT_QUEUE_DETAIL_ITEM', 'connect-detail'),
            'tries' => (int) env('CONNECT_DETAIL_ITEM_TRIES', 5),
            'timeout' => (int) env('CONNECT_DETAIL_ITEM_TIMEOUT', 180),
        ],
        'detail_list' => [
            'queue' => env('CONNECT_QUEUE_DETAIL_LIST', 'default'),
            'tries' => (int) env('CONNECT_DETAIL_LIST_TRIES', 3),
            'timeout' => (int) env('CONNECT_DETAIL_LIST_TIMEOUT', 300),
        ],
        'endpoint' => [
            'queue' => env('CONNECT_QUEUE_ENDPOINT', 'default'),
            'tries' => (int) env('CONNECT_ENDPOINT_TRIES', 3),
            'timeout' => (int) env('CONNECT_ENDPOINT_TIMEOUT', 300),
        ],
        'fetch' => [
            'queue' => env('CONNECT_QUEUE_FETCH', 'default'),
            'tries' => (int) env('CONNECT_FETCH_TRIES', 3),
            'timeout' => (int) env('CONNECT_FETCH_TIMEOUT', 300),
        ],
        'transform' => [
            'queue' => env('CONNECT_QUEUE_TRANSFORM', 'default'),
            'tries' => (int) env('CONNECT_TRANSFORM_TRIES', 3),
            'timeout' => (int) env('CONNECT_TRANSFORM_TIMEOUT', 300),
        ],
        'finalize_detail' => [
            'queue' => env('CONNECT_QUEUE_FINALIZE_DETAIL', 'default'),
            'tries' => (int) env('CONNECT_FINALIZE_DETAIL_TRIES', 20),
            'timeout' => (int) env('CONNECT_FINALIZE_DETAIL_TIMEOUT', 120),
        ],
        'tree' => [
            'queue' => env('CONNECT_QUEUE_TREE', 'default'),
            'tries' => (int) env('CONNECT_TREE_TRIES', 3),
            'timeout' => (int) env('CONNECT_TREE_TIMEOUT', 120),
        ],
        'tree_level' => [
            'queue' => env('CONNECT_QUEUE_TREE_LEVEL', 'default'),
            'tries' => (int) env('CONNECT_TREE_LEVEL_TRIES', 3),
            'timeout' => (int) env('CONNECT_TREE_LEVEL_TIMEOUT', 600),
        ],
    ],
];
