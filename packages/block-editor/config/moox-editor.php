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

        /*
        |--------------------------------------------------------------------------
        | Template Permissions
        |--------------------------------------------------------------------------
        |
        | Used by TemplatePolicy when Spatie Permission is installed and the
        | named permission exists in the database. Override names here if your
        | host uses a different permission naming scheme.
        |
        */
        'permissions' => [
            'view_any' => 'ViewAny:Template',
            'view' => 'View:Template',
            'create' => 'Create:Template',
            'update' => 'Update:Template',
            'delete' => 'Delete:Template',
        ],
    ],
];
