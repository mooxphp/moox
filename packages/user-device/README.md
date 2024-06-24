![Moox UserDevice](https://github.com/mooxphp/moox/raw/main/art/banner/user-device.jpg)

# Moox UserDevice

This is my package user-device

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/user-device
php artisan mooxuser-device:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

Here are some things missing, like an overview with screenshots about this package, or simply a link to the package's docs.

<!--/whatdoes-->

## Manual Installation

Instead of using the install-command `php artisan mooxuser-device:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="user-device-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="user-device-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
