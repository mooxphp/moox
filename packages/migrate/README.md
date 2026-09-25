# moox Migrate

Simple moox Entity for Laravel's existing `migrations` table. Lists applied migrations and runs `php artisan migrate` from Filament.

## Features

- List of rows from the existing `migrations` table
- Searchable by migration name
- Filterable by batch
- No create, view, edit, or delete
- Header action **Migrate** with confirmation modal

## Requirements

See [Requirements](https://github.com/mooxphp/moox/blob/main/docs/Requirements.md).

## Installation

```bash
composer require moox/migrate
php artisan moox:install
```

Curious what the install command does? See [Installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md).

Register the plugin on your Filament panel:

```php
use Moox\Migrate\Plugins\MigratePlugin;

$panel->plugins([
    MigratePlugin::make(),
]);
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
