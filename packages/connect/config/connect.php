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
];
