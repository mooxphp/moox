![Moox Sync](https://github.com/mooxphp/moox/raw/main/art/banner/sync.jpg)

# Moox Sync

Moox Sync is under hard development.

Moox Sync enables you to synchronize records between Filament platforms.

## Manage Platforms

First, you need to create a platform, better two. Even if you would be able to sync on the same platform (but different model), that is not the main idea of Sync.

## Manage Syncs

Then you are able to create a Sync between platforms. Choose source and target platform and model, add parameters, sync.

## How Sync works?

Here are some key components and the basic flow of Sync:

- **Moox Sync API**
  - `Platforms`: Manage and retrieve platform configurations.
  - `Syncs`: Manage sync configurations, including CRUD operations for sync setups.
  - `Syncs for Platform`: Retrieve sync configurations specific to a platform. This API is essential for the `SyncApiJob` to query the latest sync configurations.

- **SyncApiJob**

  - Periodically (or on-demand) fetch sync configurations from the Moox Sync API to ensure that the latest sync rules are applied.
  - Flow:
    - Triggered based on a defined schedule or event.
    - Queries the Moox Sync API for sync configurations related to a specific platform.
    - Refreshes the local sync table to reflect the latest sync setups.

- **SyncListener**

  - Monitor model events (e.g., create, update, delete) on the source platform.
  - Flow:
    - Attached to models specified in the sync configuration.
    - When an event occurs (e.g., a new record is created), the listener triggers.
    - The listener then invokes a Webhook on the target platform by sending the relevant data (e.g., model data) through an HTTP request to the `SyncWebhook`.

- **SyncWebhook**

  - Act as the entry point on the target platform, receiving data from the source platform via the `SyncListener`.
  - Flow:
    - Receives the data from the source platform.
    - Validates the incoming data and checks for any transformation or field mapping requirements specified in the sync configuration.
    - Triggers the `SyncJob` with the validated and transformed data.

  **SyncJob**

  - Perform the actual data synchronization on the target platform.
  - Flow:
    - Executes the query on the target platform to create, update, or delete records based on the data received from the source platform.
    - Handles conditions like conflict resolution (e.g., updating existing records if they match certain criteria).
    - Logs success or failure, including error handling (e.g., retry logic if the sync fails due to temporary issues).

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/sync
php artisan mooxsync:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

This is my package sync

<!--/whatdoes-->

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
