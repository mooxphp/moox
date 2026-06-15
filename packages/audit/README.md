![Moox Audit](https://github.com/mooxphp/moox/raw/main/art/banner/audit.jpg)

# Moox Audit

Moox Audit provides centralized activity logging and a read-only Filament audit UI on top of [Spatie Laravel Activity Log](https://github.com/spatie/laravel-activitylog).

## Quick Installation

```bash
composer require moox/audit
php artisan mooxaudit:install
```

## How it works

Audit uses three configuration layers (lowest to highest priority):

1. **Presets** in `config/audit.php` (`draft_main`, `draft_translation`, …)
2. **Package defaults** — each Moox package registers an `audit` block (e.g. `category.audit`)
3. **App overrides** in published `config/audit.php` (`models`, `hooks`, `filament`)

When `audit.enabled=true`, configured models are tracked automatically. **No trait** and **no model changes** in consumer packages.

## Package integration

In your package config (example `category.php`):

```php
'audit' => [
    'models' => [
        Category::class => [
            'preset' => 'draft_main',
            'log_name' => 'category',
            'attributes' => ['status', 'scope'],
        ],
    ],
],
```

In your `ServiceProvider`:

```php
if (class_exists(AuditPackageRegistry::class) && config('audit.enabled', true)) {
    AuditPackageRegistry::register('category', config('category.audit', []));
}
```

For Filament activity tabs, add to your Resource (loose coupling via `class_exists`):

```php
public static function getRelations(): array
{
    $relations = parent::getRelations();

    if (class_exists(\Moox\Audit\Support\AuditResourceRelationRegistry::class)) {
        $relations = array_merge(
            $relations,
            \Moox\Audit\Support\AuditResourceRelationRegistry::for(static::class),
        );
    }

    return $relations;
}
```

Or use the optional trait `Moox\Audit\Filament\Concerns\InteractsWithAuditResourceRelations`.

## App overrides

Disable a model:

```php
'models' => [
    Category::class => ['enabled' => false],
],
```

Replace attribute list:

```php
'models' => [
    Category::class => ['attributes' => ['status', 'scope']],
],
```

Append fields:

```php
'models' => [
    Category::class => ['append_attributes' => ['due_at']],
],
```

## Manual Installation

```bash
php artisan vendor:publish --tag="audit-migrations"
php artisan migrate
php artisan vendor:publish --tag="audit-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
