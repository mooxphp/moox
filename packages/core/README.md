![Moox Core](https://github.com/mooxphp/moox/raw/main/art/banner/core.jpg)

# Moox Core

The Moox Core package cares for many common features. It is required by all Moox packages.

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/core
php artisan mooxcore:install
```

Curious what the install command does? See manual installation below.

## Manual Installation

Instead of using the install-command `php artisan mooxcore:install` you are able to install this package manually step by step:

```bash
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
