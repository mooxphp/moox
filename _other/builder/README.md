![Moox Builder](https://github.com/mooxphp/moox/raw/main/_other/art/banner/builder.jpg)

# Moox Builder

This template is used for generating all Moox packages. Press the Template-Button in GitHub, to create your own.

If you install it, it will completely work without beeing useful. Guaranteed!

## Installation

Install the package via composer:

```bash
composer require moox/builder
```

and run the install command for this package:

```bash
php artisan `mooxbuilder:install`
```

The install command does the following. Alternatively you can run as single commands.

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="builder-migrations"
php artisan migrate
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="builder-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
