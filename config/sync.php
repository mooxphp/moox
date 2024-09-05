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
    | Sync Platform Job
    |--------------------------------------------------------------------------
    |
    | Enable or disable the Sync Platform Job that automatically syncs data
    | between all platforms. Use this Job only on one platform, preferably
    | that platform that is configured as master. Should not be a target
    | platform.
    |
    */

    'sync_platform_job' => [
        'enabled' => env('SYNC_PLATFORM_JOB_ENABLED', false),
        'frequency' => 'everyFiveMinutes', // hourly, daily, hourly, etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Backup Job
    |--------------------------------------------------------------------------
    |
    | Enable or disable the Sync Backup Job that automatically syncs data
    | based on your sync configurations, when changes are made outside
    | of Eloquent events or you've disabled the Eloquent listener.
    | Enable the Backup Job only on source platforms.
    |
    */

    'sync_backup_job' => [
        'enabled' => env('SYNC_BACKUP_JOB_ENABLED', false),
        'frequency' => 'everyFiveMinutes', // hourly, daily, hourly, etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Eloquent Listener
    |--------------------------------------------------------------------------
    |
    | Enable or disable the Eloquent listener that listens to model
    | events and invokes the Sync Webhook on the target platforms.
    | So the listener should be enabled on source platforms.
    | Plays nice together with the sync backup job.
    |
    */

    'sync_eloquent_listener' => [
        'enabled' => env('SYNC_LISTENER_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Webhook
    |--------------------------------------------------------------------------
    |
    | Enable or disable the webhook that automatically syncs
    | data when a model is created, updated or deleted.
    | Enable it on all target platforms.
    |
    */

    'sync_webhook' => [
        'enabled' => env('SYNC_WEBHOOK_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Models with Platform Relations
    |--------------------------------------------------------------------------
    |
    | List of models that should have platform relations.
    |
    */

    'models_with_platform_relations' => [
        'App\Models\User',
        'Moox\User\Models\User',
        'Moox\Press\Models\User',
        // Add any other models here
    ],

    /*
    |--------------------------------------------------------------------------
    | // TODO: Models with syncable Relations - not implemented yet
    |--------------------------------------------------------------------------
    |
    | List of models that should have syncable relations, which should be
    | synced to other platforms, when changes are made. This does not
    | add the related models to the listener, but syncs them with
    | the sync model automatically. So platform-related models
    | (like WpUsers and WpUserMetaare able to use this
    | feature, too.
    |
    */

    'models_with_syncable_relations' => [
        'Moox\User\Models\User',
        'Moox\Press\Models\WpUser' => [
            'Moox\UserSession\Models\Session',
        ],
        // Add any other models here
    ],

    /*
    |--------------------------------------------------------------------------
    | Unique Identifier Fields
    |--------------------------------------------------------------------------
    |
    | The synced model should have a unique identifier. The id auto-
    | increments, so it is not suitable. Perfect would be a ULID
    | or UUID, but any other unique identifier will work, too.
    | This is the list of identifiers Moox Sync searches for.
    | for, in the given order. Ad more as you need them.
    |
    */

    'unique_identifier_fields' => [
        'ulid',
        'uuid',
        'slug',
        'name',
        'title',
    ],

    /*
    |--------------------------------------------------------------------------
    | Local Identifier Fields
    |--------------------------------------------------------------------------
    |
    | These are the fields that are used as unique identifiers for
    | the models. They are used to identify the models on the
    | source platform. The array is sorted by priority.
    |
    */

    'local_identifier_fields' => [
        'ID',
        'uuid',
        'ulid',
        'id',
    ],
];
