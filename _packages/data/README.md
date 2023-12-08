![Moox Data](https://github.com/mooxphp/moox/raw/main/_other/art/banner/data.jpg)

# Moox Data

This is my package data

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/data
php artisan mooxdata:install
```

Curious what the install command does? See manual installation below.

## What does it do?

<!--whatdoes-->
This is my package data
<!--/whatdoes-->


## Manual Installation

Instead of using the install-command `php artisan mooxdata:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="data-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="data-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
