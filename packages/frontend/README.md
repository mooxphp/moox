![Moox Frontend](https://github.com/mooxphp/moox/raw/main/art/banner/frontend-package.jpg)

# Moox Frontend

Moox Frontend is a package that provides a modular frontend for a CMS, Shop, Blog or any other Laravel and Filament project, that needs a frontend. Moox Frontend provides

-   Frontend routing for resources (items including taxonomies)
-   Resolving URL conflicts between resources
-   Theme support, using Moox Themes
-   A preview feature for unpublished or soft-deleted resources

And the Pro version adds:

-   Caching, static HTML and using a CDN

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/frontend
php artisan frontend:install
```

Curious what the install command does? See manual installation below.

## Manual Installation

Instead of using the install-command `php artisan frontend:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="frontend-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="frontend-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
