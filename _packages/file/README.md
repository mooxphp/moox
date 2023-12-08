![Moox File](https://github.com/mooxphp/moox/raw/main/_other/art/banner/file.jpg)

# Moox File

This is my package file

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/file
php artisan mooxfile:install
```

Curious what the install command does? See manual installation below.

## What does it do?

<!--whatdoes-->
This is my package file
<!--/whatdoes-->


## Manual Installation

Instead of using the install-command `php artisan mooxfile:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="file-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="file-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
