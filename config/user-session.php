<?php

use Moox\Press\Models\WpUser;
use Moox\User\Models\User;
use Moox\UserDevice\Models\UserDevice;

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
                'my' => [
                    'label' => 'My Sessions',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'user_id',
                            'operator' => '=',
                            'value' => fn () => auth()->user()->id,
                        ],
                    ],
                ],
                'user' => [
                    'label' => 'trans//core::session.user_sessions',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'user_type',
                            'operator' => '=',
                            'value' => User::class,
                            // TODO: Not implemented yet
                            'hide-if-not-exists' => true,
                        ],
                    ],
                ],
                'wpuser' => [
                    'label' => 'trans//core::press.press_sessions',
                    'icon' => 'gmdi-account-circle',
                    'query' => [
                        [
                            'field' => 'user_type',
                            'operator' => '=',
                            'value' => WpUser::class,
                            // TODO: Not implemented yet
                            'hide-if-not-exists' => true,
                        ],
                    ],
                ],
                'anonymous' => [
                    'label' => 'trans//core::session.anonymous_sessions',
                    'icon' => 'gmdi-no-accounts',
                    'query' => [
                        [
                            'field' => 'user_id',
                            'operator' => '=',
                            'value' => null,
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
        'Moox Users' => User::class,
    ],
    'device_model' => UserDevice::class,

    /*
    | Session expiry for different scopes, currently not implemented!
    */
    'session_expiry' => [
        'Default' => 1, // day
        'Whitelisted' => 365, // days
    ],

    /*
    | Whitelisted IPs or IP ranges can be used to extend the session expiry.
    | They are also used to distinguish between internal and external IPs
    | in the user session list.
    */
    'whitelisted_ips' => [
        'heco Network' => '62.26.138.101',
    ],
];
