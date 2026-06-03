![Moox Connect](https://github.com/mooxphp/moox/raw/main/art/banner/connect-package.jpg)

# Moox Connect

Moox Connect is a Laravel 11+ and Filament 3.2 package that allows managing REST and GraphQL API endpoints, synchronizing data from APIs to a database, and providing Filament-powered UI for administration.

Moox Connect is under heavy development.

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/connect
php artisan connect:install
```

Curious what the install command does? See manual installation below.

## Features

-   UI for managing APIs, endpoints and jobs to sync data
-   Support for REST and GraphQL APIs
-   Flexible endpoint configuration
-   Rate limiting with automatic retry
-   Multiple auth strategies:
    -   Basic Auth
    -   Bearer Token
    -   OAuth 2.0
    -   JWT
    -   Multi-Auth with token inheritance
-   Automatic token refresh
-   Secure credential storage
-   Built-in transformers:
    -   Array/Collection
    -   DateTime
    -   JSON
    -   Number
-   Custom transformer support
-   Validation and type safety
-   Job scheduling and queues
-   Status monitoring
-   Comprehensive logging
-   Error recovery with retry mechanisms
-   Email notifications for failures

## What It Does

Moox Connect lets you configure REST and GraphQL API endpoints and then synchronize data from those APIs into your database. It does that by:

-   Storing raw API responses as `api_import_records` (optionally chunked into `api_import_payload_chunks`)
-   Transforming those stored payloads into rows in your destination models (via `destination_model`, `field_mappings`, `key_fields`, and optional transformers)
-   Coordinating multi-level "list -> detail" syncs using parent/child endpoint relationships

It also ships a Filament admin UI so you can manage connections/endpoints and run sync jobs from the backend.

## How the Sync Works (Under the Hood)

At a high level, Moox Connect is a queue-driven pipeline:

1.  **Orchestration (optional, for endpoint trees)**  
    `RunConnectionTreeJob` builds a BFS tree (root endpoints at depth 0) and executes each tree level as a queue batch via `RunConnectionTreeLevelJob`.  
    Level 0 endpoints run with `RunEndpointJob`, deeper nodes (children) run with `RunDetailForListJob`.

2.  **Fetch & store import record**  
    `RunEndpointJob` calls `ApiEndpointRunner->run(...)`.  
    The runner requests the configured endpoint (REST or GraphQL), normalizes/parses the response, and writes an `ApiImportRecord` in `api_import_records` (plus optional chunk rows in `api_import_payload_chunks` for large payloads).

3.  **List -> detail expansion (children)**  
    `RunDetailForListJob` uses `EndpointListToDetailOrchestrator` to build follow-up jobs from the parent payload. Depending on the endpoint configuration it will create:
    -   `FetchImportRecordsJob` for bulk list fetching
    -   `RunEndpointForItemJob` for per-item detail requests

4.  **Transform into destination model(s)**  
    `TransformImportRecordsJob` claims import records using safe DB semantics (claiming with `FOR UPDATE SKIP LOCKED` and a `WithoutOverlapping` lock), reconstructs chunked payloads, and then maps payload fields into the configured `destination_model`.

    After successful destination writes it updates import record status (for example to `processed`); failures mark import records as `failed`.

5.  **Pruning (for `sync_mode = sync`)**  
    When endpoints are configured for "replace/sync", stale records are pruned after the replacement batch finishes.

6.  **Observability**  
    Each request and job run creates entries in `api_logs`. Import records also expose `status` so you can monitor progress in the Filament UI.

## Scopes (sync_scope_fields / sync_scope_hash)

In list->detail synchronization you need a stable grouping key so "detail" jobs can be derived from the correct slice of the parent payload.

Moox Connect models this with **scopes**:

-   `ApiEndpoint.sync_scope_fields` defines which values inside each list item are used to compute a scope hash.
-   `ApiImportRecord.sync_scope_hash` is derived from those scope values and is used when:
    -   deciding which parent slice a detail record belongs to,
    -   claiming/importing only the relevant subset during transformation,
    -   pruning stale records when `sync_mode = sync`.

If an endpoint has no configured scope values (or the resulting scope hash would be empty), Moox Connect can fall back to an **identity-derived scope** so the record identity stays unique and consistent (even when `sync_scope_hash` would otherwise be `NULL`).

## Pruning Semantics (sync_mode = sync)

When an endpoint is configured for replacement/sync, Moox Connect does not delete/prune stale records upfront. Instead, it prunes only after the replacement work for the corresponding replacement batch has completed successfully.

This prevents the classic race condition where detail jobs are still running while the old data is already deleted.


## Further Reading

## Main Capabilities

-   REST and GraphQL endpoint execution
-   Multiple authentication strategies (Basic, Bearer, OAuth2, JWT, and Multi-Auth)
-   Automatic token refresh (when the configured auth strategy supports it)
-   Rate limiting with automatic retry (configurable globally/per endpoint/per job)
-   Data mapping into destination models using:
    -   `field_mappings` (external -> internal field mapping)
    -   `key_fields` (for upsert/update semantics)
    -   optional `transformers`
-   Queue/batch orchestration for multi-level sync trees
-   Filament admin UI (resources and widgets) for managing connections/endpoints and tracking status
-   Logging and error recovery (job retries + HTTP/DB error handling)

## How to Use

1.  Install
    -   `composer require moox/connect`
    -   `php artisan connect:install`

2.  Configure your API connections and endpoints in the Filament admin:
    -   Create an `ApiConnection` (base URL, auth type, credentials/headers, rate settings, activate it)
    -   Create one or more `ApiEndpoint` records:
        -   REST: configure `path` and `method`
        -   GraphQL: configure `query`/`variables`
        -   Configure parent/child relationships (`parent_endpoint_id`) for list->detail flows
        -   Configure extraction and identity:
            -   `response_map` (for list item extraction)
            -   `external_key_field` and optional `sync_scope_fields`
        -   Configure transformation:
            -   `destination_model`
            -   `field_mappings` (payload field -> destination field)
            -   `key_fields` (upsert identity for destination)
        -   Configure sync mode:
            -   `sync_mode` (e.g. append vs sync/replace)

3.  Run sync jobs:
    -   In the Filament `ApiEndpointResource` table you can trigger the correct job in the background.
    -   Or dispatch jobs directly from code:

```php
use Moox\Connect\Jobs\RunEndpointJob;
use Moox\Connect\Jobs\RunConnectionTreeJob;

RunEndpointJob::dispatch($endpointId);
RunConnectionTreeJob::dispatch($apiConnectionId);
```

## Database Tables & Status Semantics

Moox Connect writes/imports into (at minimum):

-   `api_connections`
-   `api_endpoints`
-   `api_logs`
-   `api_import_records`
-   `api_import_payload_chunks`

`api_import_records.status` is used by the pipeline to decide what to transform next. In this project it is commonly one of:

-   `new`
-   `fetched`
-   `processing`
-   `update`
-   `processed`
-   `failed`

## Further Reading

-   See `STRUCTURE.md` for class-by-class responsibilities and examples.
-   See `MODELS.md` for database model fields/relationships.

## Configuration

```php
return [
    'notifications' => [
        'email' => env('MAIL_TO_ADDRESS', config('mail.to.address')),
    ],

    'rate_limits' => [
        'global' => [
            'max_requests' => 1000,  // requests
            'window' => 60,          // seconds
        ],

        'per_endpoint' => [
            'default' => [
                'max_requests' => 100,
                'window' => 60,
            ],
            // Can be overridden per endpoint in endpoint config
        ],

        'per_job' => [
            'default' => [
                'max_requests' => 50,
                'window' => 60,
            ],
            // Can be overridden in job config
        ],
    ],
];
```

## Classes and Models

You can deep dive into the [Class Structure](STRUCTURE.md) and the [Database Models](MODELS.md) of this package, if you want to know more about the inner workings.

## Manual Installation

Instead of using the install-command `php artisan connect:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="connect-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="connect-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
