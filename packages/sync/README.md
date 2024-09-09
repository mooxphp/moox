![Moox Sync](https://github.com/mooxphp/moox/raw/main/art/banner/sync.jpg)

# Moox Sync

Moox Sync is under hard development.

Moox Sync enables you to synchronize records between Moox platforms or other Filament and Laravel platforms.

https://github.com/user-attachments/assets/877e52a8-3f7b-4527-ab75-03996155ec41

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/sync
php artisan mooxsync:install
```

Curious what the install command does? See manual installation below.

## Create Platforms

First, you need to create a platform, better two. You would be able to sync on the same platform (but different model of course), but that is not the main idea of Sync.

![Create Sync Platform](https://github.com/mooxphp/moox/raw/main/art/screenshot/sync-plattform-create.jpeg)

![List Sync Platforms](https://github.com/mooxphp/moox/raw/main/art/screenshot/sync-plattforms.jpeg)

## Create Syncs

Then you are able to create a Sync between platforms.

![Create Syncs like this](https://github.com/mooxphp/moox/raw/main/art/screenshot/sync-sync-edit.jpeg)

![List your Syncs](https://github.com/mooxphp/moox/raw/main/art/screenshot/sync-syncs.jpeg)

## The basic flow

### SyncPlattformsJob

Platforms are automatically synced between all platforms. For the first time done with basic security only using the Sync Token from config, afterwards adding the platform-token to provide first class security including HMAC.

The SyncPlatformsJob can be enabled via config or .env. This should run on not more than ONE platform.

As an alternative to running the job periodically (minutely by default), you can manually start the Job by pressing the `Sync Platforms` button after making updates to platforms.

### SyncListener

The SyncListener runs an Eloquent Event Listener for each Sync and catches up all model changes done with Eloquent. If you change data in models without using Eloquent, you may consider firing an event:

```php
Event::dispatch('eloquent.updated: '.get_class($this->record), $this->record);
```

Note: For handling imports or changes done outside of Laravel, we recommend running the `SyncBackupJob`.

The SyncListener runs the `PrepareSynJob`.

The SyncListener needs to be activated in Moox Sync Configuration. That should be done on Source platforms only.

### PrepareSyncJob

The PrepareSyncJob is invoked by the SyncListener. It prepares the data and triggers the `SyncWebhook`.

The PrepareSyncJob supports custom data handling like custom queries, data manipulation, so called Transformers, also available as Transformer Bindings, configured in Moox Sync Configuration.

### Transformer

Transformers are not implemented yet. We recommend using Transformer Bindings instead.

### Transformer Bindings

See how `WpUserTransformer` (in Moox Press) extends `AbstractTransformer` on how to implement a transformer binding. Register your transformer binding in the Moox Sync config.

### SyncWebhook

Act as the entry point on the target platform, receiving data from the source platform via the `PrepareSyncJob`. Validates the incoming data using HMAC and checks for any transformation or field mapping requirements specified in the sync configuration.

Triggers the `SyncJob` with the validated and transformed data.

The SyncWebhook needs to be activated in Moox Sync Configuration. It needs to be enabled on Target platforms only.

### SyncJob

The SyncJob queues the actual sync by using the `SyncService`.

### SyncService

The Sync writes the data on the Target platform. It supports `Custom SyncHandler`and `PlatformRelations`.

### SyncHandler

See how `WpUserSyncHandler`(in Moox Press) extends `AbstractSyncHandler`on how to implement your own SyncHandler. Register your sync handler in the Moox Sync config.

### PlatformRelations

The `PlatformRelationService` is an optional feature, you can set for every sync. It is a key component of Moox Sync that handles the relationships between models and platforms. It provides methods for syncing and retrieving platform associations for any model.

Key methods:

-   `syncPlatformsForModel($model, array $platformIds)`: Syncs the platforms for a given model.
-   `getPlatformsForModel($model)`: Retrieves the platforms associated with a given model.

### SyncBackupJob

Not implemented yet.

## Security

Platform Token and HMAC.

## Config

Moox Sync is highly configurable via the `sync.php` config file. Here are the available options:

```php
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
    | Models with Platform Relations
    |--------------------------------------------------------------------------
    |
    | List of models that should have platform relations. This adds the
    | platforms relation to the model. No need to add a trait or
    | any dependency to the model or the package.
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
];

```

### Logging

Setting up Sync involves the connection of two or more platforms, availability of APIs and running Jobs. This is why we added a logger to the package, that can be setup in [Moox Core config](../core/README.md#logging). The flow of a working sync may look like this. Depending on the log level you get very detailed information about the data flow in Moox Sync. On production anything else than 0 should not be the default, but can perfectly used to implement or debug Moox Sync.

## Manual Installation

Instead of using the install-command `php artisan mooxsync:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="sync-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="sync-config"
```

Edit your PanelProvider to add both Plugins to your Navigation.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
