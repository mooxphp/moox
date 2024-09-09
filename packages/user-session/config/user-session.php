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

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'session' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::session.session',
            'plural' => 'trans//core::session.sessions',

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
                'user' => [
                    'label' => 'Alf Sessions',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'user_name',
                            'relation' => 'user',
                            'operator' => '!=',
                            'value' => 'Alf',
                        ],
                    ],
                ],
            ],
        ],
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

    /*
    |--------------------------------------------------------------------------
    | Audit - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 6400,

    /*
    | You can provide multiple user models for session management.
    | And you can use your own device model to store the device information.
    */
    'user_models' => [
        'App Users' => \App\Models\User::class,
        'Moox Users' => \Moox\User\Models\User::class,
    ],
    'device_model' => \Moox\UserDevice\Models\UserDevice::class,

    /*
    | Session expiry for different scopes,
    | // TODO: currently not implemented!
    */
    'session_expiry' => [
        'Default' => 1, // day
        'Whitelisted' => 365, // days
    ],

    /*
    | Whitelisted IPs or IP ranges can be used to extend the session expiry.
    | They are also used to distinguish between internal and external IPs
    | in the user session list.
    | // TODO: currently not implemented!
    */
    'whitelisted_ips' => [
        //'Your Network' => '61.2.101.101',
    ],
];
