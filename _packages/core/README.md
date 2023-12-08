![Moox Core](https://github.com/mooxphp/moox/raw/main/_other/art/banner/core.jpg)

# Moox Core

This is my package core

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/core
php artisan mooxcore:install
```

Curious what the install command does? See manual installation below.

## What does it do?

<!--whatdoes-->
This is my package core
<!--/whatdoes-->


## Manual Installation

Instead of using the install-command `php artisan mooxcore:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="core-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="core-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
