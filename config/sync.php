<?php

use App\Models\User;
use Moox\Press\Handlers\WpUserSyncHandler;
use Moox\Press\Models\WpUser;
use Moox\Press\Resolver\WpUserFileResolver;
use Moox\Press\Transformer\WpUserTransformer;
use Moox\Sync\Http\Controllers\Api\PlatformController;
use Moox\Sync\Http\Controllers\Api\SyncController;
use Moox\Sync\Models\Platform;
use Moox\Sync\Models\Sync;
use Moox\Sync\Resources\PlatformResource;
use Moox\Sync\Resources\SyncResource;

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
            'model' => Sync::class,
            'resource' => SyncResource::class,
            'api_controller' => SyncController::class,
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
            'model' => Platform::class,
            'resource' => PlatformResource::class,
            'api_controller' => PlatformController::class,
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
    | List of models that should have platform relations.
    |
    */

    'models_with_platform_relations' => [
        User::class,
        \Moox\User\Models\User::class,
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
        \Moox\User\Models\User::class,
        WpUser::class => [
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

    // TODO: Not implemented yet, use bindings instead
    'transformer_classes' => [
        // TODO: Add translation transformer as example
        // Moox\Sync\Transformer\TranslationTransformer,
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
        WpUser::class => WpUserTransformer::class,
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
        WpUser::class => WpUserSyncHandler::class,
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

    /*
    |--------------------------------------------------------------------------
    | Enable File Sync
    |--------------------------------------------------------------------------
    |
    | Enables the file sync feature, which allows you to sync files between
    | platforms, that can be auto-detected in your models based on
    | configurable rules or by using a custom resolver.
    |
    */

    'file_sync' => [
        'enabled' => env('FILE_SYNC_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Sync Allowed Extensions
    |--------------------------------------------------------------------------
    |
    | The file sync allowed extensions are used to check if the file extension
    | is allowed to be synced. You may add more extensions as needed.
    |
    */

    'file_sync_allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'svg',
        'webp',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Sync Mode
    |--------------------------------------------------------------------------
    |
    | The file sync mode is used to determine the underlying
    | file sync method. Currently only http is supported.
    | Rsync is planned for future implementation.
    |
    */

    'file_sync_mode' => 'http', // TODO: rsync not implemented yet

    /*
    |--------------------------------------------------------------------------
    | File Sync Size
    |--------------------------------------------------------------------------
    |
    | The file sync max size is used to limit the size of the file
    | that can be synced over http or rsync (planned).
    | The chunk size is only relevant for http.
    |
    */

    'file_sync_max_size_http' => 2 * 1024 * 1024, // 5 MB
    'file_sync_max_size_rsync' => 50 * 1024 * 1024, // 50 MB, not implemented yet
    'file_sync_chunk_size_http' => 1024 * 1024, // 1 MB

    /*
    |--------------------------------------------------------------------------
    | File Sync Files Count
    |--------------------------------------------------------------------------
    |
    | The file sync files count is used to limit the number
    | of files that can be synced over http or
    | rsync (planned) per sync record.
    |
    */

    'file_sync_files_count_http' => 10,
    'file_sync_files_count_rsync' => 100, // TODO: rsync not implemented yet

    /*
    |--------------------------------------------------------------------------
    | File Sync Temp Directory
    |--------------------------------------------------------------------------
    |
    | The file sync temp directory is used to store the file chunks
    | during transfer. It should be writable by the webserver
    | user, not tracked by git and maybe not backed up.
    |
    */

    'file_sync_temp_directory' => 'temp/file_sync',

    /*
    |--------------------------------------------------------------------------
    | File Sync Fieldsearch
    |--------------------------------------------------------------------------
    |
    | The file sync fields are used to detect the file sync for a model.
    | If any of these words are found within the column name,
    | the content will be checked for being a file path.
    |
    */

    'file_sync_fieldsearch' => [
        'file',
        'image',
        'media',
        'attachment',
        'avatar',
        'logo',
        'thumbnail',
        'cover',
        'banner',
        'picture',
        'image',
        'photo',
        'picture',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Sync Resolver
    |--------------------------------------------------------------------------
    |
    | The file sync resolver is used to resolve the file sync for a model.
    | You can activate Moox Press resolver here or create your own.
    |
    */

    'file_sync_resolver' => [
        WpUser::class => WpUserFileResolver::class,
    ],
];
