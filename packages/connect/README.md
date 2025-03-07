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
