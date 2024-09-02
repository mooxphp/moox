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
        'sync' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::sync.sync',
            'plural' => 'trans//core::sync.syncs',

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
        ],
        'platform' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::sync.platform',
            'plural' => 'trans//core::sync.platforms',

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

    'navigation_group' => 'trans//core::core.tools',

    /*
    |--------------------------------------------------------------------------
    | Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This values are the sort order of the navigation items in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 9500,

    /*
     |--------------------------------------------------------------------------
     | API
     |--------------------------------------------------------------------------
     |
     | Enable or disable the API and configure all entities.
     | Public or secured by platform or sanctum.
     | Available at /api/{entity}
     |
     */

    'entities' => [
        'Sync' => [
            'api' => [
                'enabled' => true,
                'public' => false,
                'auth_type' => 'platform',
                'active_routes' => [
                    'index',
                    'show',
                    'store',
                    'update',
                    'destroy',
                ],
            ],
            'model' => '\Moox\Sync\Models\Sync',
            'resource' => '\Moox\Sync\Resources\SyncResource',
            'api_controller' => '\Moox\Sync\Http\Controllers\Api\SyncController',
        ],
        'Platform' => [
            'api' => [
                'enabled' => true,
                'public' => false,
                'auth_type' => 'platform',
                'active_routes' => [
                    'index',
                    'show',
                    'store',
                    'update',
                    'destroy',
                ],
            ],
            'model' => '\Moox\Sync\Models\Platform',
            'resource' => '\Moox\Sync\Resources\PlatformResource',
            'api_controller' => '\Moox\Sync\Http\Controllers\Api\PlatformController',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Backup Job
    |--------------------------------------------------------------------------
    |
    | Enable or disable the Sync Backup Job that automatically syncs data
    | based on your sync configurations, when changes are made outside
    | of Eloquent events or you've disabled the Eloquent listener.
    |
    */

    'sync_backup_job' => [
        'enabled' => true,
        'frequency' => 'everyFiveMinutes', // hourly, daily, hourly, etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Eloquent Listener
    |--------------------------------------------------------------------------
    |
    | Enable or disable the Eloquent listener that automatically syncs
    | data when a model is created, updated or deleted. Use
    | it wisely together with the Sync Backup Job.
    |
    */

    'sync_eloquent_listener' => [
        'enabled' => true,
    ],

];
