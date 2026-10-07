![Moox Impersonate](https://github.com/mooxphp/moox/raw/main/art/banner/impersonate.jpg)

# Moox Impersonate

Filament user impersonation for Moox — built on [stechstudio/filament-impersonate](https://github.com/stechstudio/filament-impersonate), with optional [moox/audit](https://github.com/mooxphp/audit) logging.

## Features

- Registers enter/leave listeners that log to moox/audit when that package is installed
- While impersonating, Spatie activity causer resolves to the real admin (via moox/audit `CauserResolver`)
- Filament action factory so resources soft-depend on this package instead of STS directly
- No hard dependency on panel packages (finance, portal, …)

## Requirements

- PHP ^8.3
- Laravel ^12
- Filament ^4|^5
- `moox/core`
- `stechstudio/filament-impersonate`

Optional: `moox/audit`

## Quick Installation

```bash
composer require moox/impersonate
php artisan vendor:publish --tag="impersonate-config"
```

Publish and customise `stechstudio/filament-impersonate` banner/redirect config as needed:

```bash
php artisan vendor:publish --tag="filament-impersonate-config"
```

## Usage

On a Filament resource (soft-coupled):

```php
use Filament\Actions\Action;
use Moox\Impersonate\Filament\Actions\ImpersonateAction;

public static function impersonateAction(): ?Action
{
    if (! class_exists(ImpersonateAction::class)) {
        return null;
    }

    return ImpersonateAction::make()
        ->guard('finance')
        ->redirectTo(fn (): string => /* panel URL */)
        ->backTo(fn (): string => static::getUrl('index'));
}
```

Targets need `canBeImpersonated(): bool` on the model; admins need `canImpersonate(): bool`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
