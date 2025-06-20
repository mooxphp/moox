![Moox Press](https://github.com/mooxphp/moox/raw/main/art/banner/press.jpg)

# Moox Press

This integrates WordPress into Filament - work in progress.

Developers notes:

-   in main composer.json you must allow the plugin for the wordpress installer
-   add the contents of deploy.sh to your deployment (e.g. on Forge) to create the symlink

```json
        "allow-plugins": {
            "roots/wordpress-core-installer": true
```

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/press
php artisan mooxpress:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

This is my package press

<!--/whatdoes-->

## Manual Installation

Instead of using the install-command `php artisan mooxpress:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="press-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="press-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
