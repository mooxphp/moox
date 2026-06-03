<?php

use Moox\User\Models\User;
use Moox\UserDevice\Resources\UserDeviceResource;

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

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'user' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::user.user',
            'plural' => 'trans//core::user.users',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Resource table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                'deleted' => [
                    'label' => 'trans//core::core.deleted',
                    'icon' => 'gmdi-delete',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '!=',
                            'value' => null,
                        ],
                    ],
                ],
                /*
                'error' => [
                    'label' => 'trans//core::core.error',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'subject_type',
                            'operator' => '=',
                            'value' => 'Error',
                        ],
                    ],
                ],
                */
            ],

            /*
            |--------------------------------------------------------------------------
            | Scopes (origin)
            |--------------------------------------------------------------------------
            |
            | Registers `user` as a scope origin (source). Child resources under
            | `scopes.allowed` are optional — add them when you need scoped nav
            | items beneath Users.
            |
            */

            'scopes' => [
                'allowed' => [
                    'user-device' => [
                        'resource' => UserDeviceResource::class,
                    ],
                ],
                'registry' => [
                    'origins' => [
                        'user' => User::class,
                    ],
                    'sources' => [
                        'user' => User::class,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth guards
    |--------------------------------------------------------------------------
    |
    | Define the columns for the username, email and password for the
    | different guards. This is necessary for the login process
    | to allow login with username or email address.
    |
    */

    'auth' => [
        'web' => [
            'username' => 'name',
            'email' => 'email',
            'password' => 'password',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Validation
    |--------------------------------------------------------------------------
    |
    | Define the password validation rules for your Moox users.
    | An empty array uses simple defaults (min 8 characters). Set
    | validation to null to disable custom password rules entirely.
    |
    */

    'password' => [
        'validation' => [
            // Defaults (when omitted): min 8, max 255, no mixed_case/numbers/symbols/uncompromised
            // 'min' => 8,
            // 'max' => 255,
            // 'mixed_case' => false,
            // 'numbers' => false,
            // 'symbols' => false,
            // 'uncompromised' => false,
        ],
        'helperText' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The translatable title of the navigation group in the
    | Filament Admin Panel. Instead of a translatable
    | string, you may also use a simple string.
    |
    */

    'navigation_group' => 'trans//core::user.users',
];
