# Installation

## Requirements

-   PHP >= 8.3
-   Laravel 11.x
-   Filament 3.2

## Installation

These two commmands are all you need to install the package:

```bash
composer require moox/skeleton
php artisan skeleton:install
```

## Manual Installation

Instead of using the install-command `php artisan skeleton:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="skeleton-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="skeleton-config"
```
