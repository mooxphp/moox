![Moox Security](https://github.com/mooxphp/moox/raw/main/art/banner/security.jpg)

# Moox Security

This is my package security

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/security
php artisan mooxsecurity:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

Here are some things missing, like an overview with screenshots about this package, or simply a link to the package's docs.

<!--/whatdoes-->

## Manual Installation

Instead of using the install-command `php artisan mooxsecurity:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="security-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="security-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
