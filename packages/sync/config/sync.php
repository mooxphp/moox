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
    | between all platforms.
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
    | Enable or disable the Eloquent listener that automatically syncs
    | data when a model is created, updated or deleted. Use
    | it wisely together with the Sync Backup Job.
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
    | data when a model is created, updated or deleted. Use
    | it wisely together with the Sync Backup Job.
    |
    */

    'sync_webhook' => [
        'enabled' => env('SYNC_WEBHOOK_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Webhook URL
    |--------------------------------------------------------------------------
    |
    | The URL that the webhook is reachable at and called at.
    |
    */

    'sync_webhook_url' => env('SYNC_WEBHOOK_URL', '/sync-webhook'),

    /*
    |--------------------------------------------------------------------------
    | Models with Platform Relations
    |--------------------------------------------------------------------------
    |
    | List of models that should be synced to other platforms, when changes are made.
    | This does not add the related models to the listener, but syncs them with
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
    | synced to other platforms, when changes are made. Adds the related
    | models to the listener, syncs them and cares for the relations.
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

    /*
    |--------------------------------------------------------------------------
    | Transformer
    |--------------------------------------------------------------------------
    |
    | You can register Transformer Classes here, to make them available
    | when creating Syncs. These classes can contain queries or
    | translation maybe. Alternatively you can bind models.
    |
    */

    // Not implemented yet, use bindings instead
    'transformer_classes' => [
        // Not implemented yet
    ],

    /*
    |--------------------------------------------------------------------------
    | Transformer Bindings
    |--------------------------------------------------------------------------
    |
    | You can register custom Transformer Bindings, used for Press models for
    | example, where we have to read meta data or custom tables like
    | terms and taxonomies instead of native categories.
    |
    */

    'transformer_bindings' => [
        // Add transformer bindings here, like:
        // \Moox\Press\Models\WpUser::class => \Moox\Press\Transformer\WpUserTransformer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Bindings
    |--------------------------------------------------------------------------
    |
    | You can register custom Sync Bindings, used for Press models for
    | example, where we have to write meta data or custom tables
    | like terms and taxonomies instead of native categories.
    |
    */

    'sync_bindings' => [
        // Add sync handlers here, like:
        // \Moox\Press\Models\WpUser::class => \Moox\Press\Handlers\WpUserSyncHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Token
    |--------------------------------------------------------------------------
    |
    | The sync token is used to authenticate the sync process even before
    | the platforms are initially synced. It must be one key for
    | all platforms that should be able to sync together.
    |
    */

    'sync_token' => env('SYNC_TOKEN', 'Y0U_N3V3R_GU355_TH15_S3CR3T_K3Y'),

];
