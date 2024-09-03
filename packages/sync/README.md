![Moox Sync](https://github.com/mooxphp/moox/raw/main/art/banner/sync.jpg)

# Moox Sync

Moox Sync is under hard development.

Moox Sync enables you to synchronize records between Moox platforms or other Filament and Laravel platforms.

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/sync
php artisan mooxsync:install
```

Curious what the install command does? See manual installation below.

## Manage Platforms

First, you need to create a platform, better two. Even if you would be able to sync on the same platform (but different model), that is not the main idea of Sync.

There are two Filament Resource to manage Platforms and Syncs.

## Manage Syncs

Then you are able to create a Sync between platforms. Choose source and target platform and model, add parameters, sync.

## How Sync works

Here are some key components and the basic flow of Sync:

-   **Moox Sync API**

    -   `Platforms`: Manage and retrieve platform configurations.
    -   `Syncs`: Manage sync configurations, including CRUD operations for sync setups.
    -   `Syncs for Platform`: Retrieve sync configurations specific to a platform. This API is essential for the ...
    -   `SyncApiJob` to query the latest sync configurations.

-   **SyncApiJob**

    -   Periodically (or on-demand) fetch sync configurations from the Moox Sync API to ensure that the latest sync rules are applied.
    -   Flow:
        -   Triggered based on a defined schedule or event.
        -   Queries the Moox Sync API for sync configurations related to a specific platform.
        -   Refreshes the local sync table to reflect the latest sync setups.

-   **SyncListener**

    -   Monitor model events (e.g., create, update, delete) on the source platform.
    -   Flow:
        -   Attached to models specified in the sync configuration.
        -   When an event occurs (e.g., a new record is created), the listener triggers.
        -   The listener then invokes a Webhook on the target platform by sending the relevant data (e.g., model data) through an HTTP request to the `SyncWebhook`.

-   **SyncWebhook**

    -   Act as the entry point on the target platform, receiving data from the source platform via the `SyncListener`.
    -   Flow:
        -   Receives the data from the source platform.
        -   Validates the incoming data and checks for any transformation or field mapping requirements specified in the sync configuration.
        -   Triggers the `SyncJob` with the validated and transformed data.

-   **SyncJob**

-   Perform the actual data synchronization on the target platform.
-   Flow:

    -   Executes the query on the target platform to create, update, or delete records based on the data received from the source platform.
    -   Handles conditions like conflict resolution (e.g., updating existing records if they match certain criteria).
    -   Logs success or failure, including error handling (e.g., retry logic if the sync fails due to temporary issues).

-   **Sync Backup Job**

    -   To ensure data consistency even when changes are made outside of Eloquent events, you can use the SyncBackupJob. This job compares and updates data based on your sync configurations.

    ```bash
    php artisan sync:backup
    ```

## Config

Besides the Moox default config options, you can can configure following options:

```php
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
```

## Services

### PlatformRelationService

The `PlatformRelationService` is a key component of Moox Sync that handles the relationships between models and platforms. It provides methods for syncing and retrieving platform associations for any model.

Key methods:

-   `syncPlatformsForModel($model, array $platformIds)`: Syncs the platforms for a given model.
-   `getPlatformsForModel($model)`: Retrieves the platforms associated with a given model.

### SyncService

The `SyncService` uses the `PlatformRelationService` to determine if a model should be synced with a target platform.

## Implementing

To add platform relations to your model, you need to edit the `models_with_platform_relations` in the `sync.php` config file and implement the platform relation into your UI, like in the following example with a Filament Resource:

### Implementing a Platform Field in a User Resource

To add platform selection functionality to a User Resource (or any other resource), you can use the following pattern:

```php
use Filament\Forms\Components\Select;
use Moox\Sync\Models\Platform;
use Moox\Sync\Services\PlatformRelationService;

public static function form(Form $form): Form
{
    return $form->schema([
        Select::make('platforms')
            ->label('Platforms')
            ->multiple()
            ->options(function () {
                return Platform::pluck('name', 'id')->toArray();
            })
            ->afterStateHydrated(function ($component, $state, $record) {
                if ($record && class_exists('\Moox\Sync\Services\PlatformRelationService')) {
                    $platformService = app(PlatformRelationService::class);
                    $platforms = $platformService->getPlatformsForModel($record);
                    $component->state($platforms->pluck('id')->toArray());
                }
            })
    ->dehydrated(false)
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, $record) {
                if ($record && class_exists('\Moox\Sync\Services\PlatformRelationService')) {
                    $platformService = app(PlatformRelationService::class);
                    $platformService->syncPlatformsForModel($record, $state ?? []);
                }
            })
            ->preload()
            ->searchable()
            ->visible(fn () => class_exists('\Moox\Sync\Models\Platform'))
            ->columnSpan([
                'default' => 12,
                'md' => 12,
                'lg' => 12,
            ]),
        ]);
}
```

### Logging

Setting up Sync involves the connection of two or more platforms, availability of APIs and running Jobs. This is why we added a logger to the package, that can be setup in [Moox Core config](../core/README.md#logging). The flow of a working sync may look like this:

```php
// TODO: Add logging example
```

## Manual Installation

Instead of using the install-command `php artisan mooxsync:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="sync-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="sync-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
