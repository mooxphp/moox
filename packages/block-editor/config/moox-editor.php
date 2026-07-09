<?php

return [
    'api' => [
        /*
        |--------------------------------------------------------------------------
        | API Route Prefix
        |--------------------------------------------------------------------------
        |
        | Prefix for all editor API routes. The templates endpoint keeps the
        | required structure and remains available as:
        | {prefix}/{version}/templates
        |
        */
        'prefix' => 'api/editor',

        /*
        |--------------------------------------------------------------------------
        | API Version
        |--------------------------------------------------------------------------
        |
        | API version segment appended after the prefix.
        | Set to '' to omit the version segment.
        |
        */
        'version' => 'v1',

        /*
        |--------------------------------------------------------------------------
        | API Middleware
        |--------------------------------------------------------------------------
        |
        | Set to null or [] to disable middleware for these routes.
        | If middleware is null/[], authorization checks are disabled by default.
        */
        'middleware' => ['web', 'auth', 'throttle:60,1'],

        /*
        |--------------------------------------------------------------------------
        | API Authorization
        |--------------------------------------------------------------------------
        |
        | true  => always enforce policies / request authorization
        | false => disable policy/request authorization
        | null  => auto mode (enabled when middleware is set, disabled otherwise)
        |
        */
        'authorization' => null,
    ],

    'dynamic_feed' => [
        'max_limit' => 50,
        'default_limit' => 5,
        'default_order_by' => 'published_at',
        'default_order_direction' => 'desc',
    ],
];
