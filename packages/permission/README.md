![Moox Permission](https://github.com/mooxphp/moox/raw/main/art/banner/permission.jpg)

# Moox Permission

Moox Permission is a thin layer around [Filament Shield](https://github.com/bezhanSalleh/filament-shield). It pulls in Shield via Composer and provides the shared `DefaultPolicy` used across Moox packages.

## Installation

```bash
composer require moox/permission
php artisan moox:install
```

The installer publishes Shield/Spatie configuration, runs the permission migrations, and registers `FilamentShieldPlugin` in your panel (via `composer.json` → `extra.moox.install.plugins`).

Shield-specific setup (`shield:generate`, roles, super admin) stays in Shield itself — not in this package.

## Optional permissions

Moox policies follow the same pattern as `HasRolesTrait` in `moox/user`:

- Shield not installed / tables missing → allow
- Permission not generated yet → allow
- Permission exists → check `can()`

That way resources stay usable after install until you explicitly generate and assign permissions.

## Default Policy

`Moox\Permission\Policies\DefaultPolicy` is the shared fallback for Moox resources when no dedicated policy exists.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
