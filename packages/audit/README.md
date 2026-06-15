![Moox Audit](https://github.com/mooxphp/moox/raw/main/art/banner/audit.jpg)

# Moox Audit

Moox Audit provides centralized activity logging and a read-only Filament audit UI on top of [Spatie Laravel Activity Log](https://github.com/spatie/laravel-activitylog).

Consumer packages register their models in config — **no trait** and **no model changes** are required. When `audit.enabled` is `true`, configured models are tracked automatically via a config-driven observer.

## Features

- **Config-driven tracking** — models, attributes, events, and presets are declared in package or app config
- **Layered configuration** — presets, package defaults, and app overrides merge predictably
- **Entry types** — distinguish compliance-style **audit** entries from general **log** entries
- **Scope support** — stores a `scope` value on activities (from the model or its draft parent)
- **Filament UI** — global `AuditResource` plus per-record **Activity** relation tabs
- **Aggregated activity views** — show activities from related models (e.g. translations) on the owner resource
- **Event hooks** — log custom lifecycle events (e.g. `categorizables_detached` on category delete)
- **User attribute enrichment** — resolve `author_id`, `created_by_id`, and `updated_by_id` to readable labels

## Requirements

- PHP ^8.3
- Laravel ^12
- Filament ^4
- `moox/core`
- `spatie/laravel-activitylog`

## Quick Installation

```bash
composer require moox/audit
php artisan mooxaudit:install
```

The install command interactively:

1. Publishes `config/audit.php`
2. Publishes and runs migrations (`activity_log` table with Moox columns)
3. Registers `Moox\Audit\Plugins\AuditPlugin` in `AdminPanelProvider` when present

Set `AUDIT_ENABLED=false` in `.env` to disable tracking globally.

## How it works

Audit uses three configuration layers (lowest to highest priority):

1. **Presets** in `config/audit.php` (`draft_main`, `draft_translation`, …)
2. **Package defaults** — each Moox package registers an `audit` block via `AuditPackageRegistry` (e.g. `category.audit`)
3. **App overrides** in published `config/audit.php` (`models`, `hooks`, `filament`)

At boot, `AuditBootstrap` (triggered by `AuditPlugin`) registers:

- Eloquent event listeners on all tracked models
- The dynamic `auditActivities` morph relation on each model
- Configured hooks and Filament relation managers

### Merge rules

| Key | App override behavior |
| --- | --- |
| `attributes`, `hidden_attributes`, `events`, `properties`, `aggregate_subjects` | **Replaces** the merged list |
| `append_attributes`, `append_hidden_attributes`, `append_properties` | **Appends** to the merged list |
| `enabled` | Set to `false` to disable a model, hook, or Filament resource |
| Other keys | Shallow override via `array_replace_recursive` |

## Package integration

### 1. Declare audit config

In your package config (example from `category.php`):

```php
'audit' => [
    'enabled' => true,
    'models' => [
        Category::class => [
            'preset' => 'draft_main',
            'log_name' => 'category',
            'attributes' => [
                'is_active',
                'status',
                'scope',
                'parent_id',
                'color',
                'weight',
            ],
        ],
        CategoryTranslation::class => [
            'preset' => 'draft_translation',
            'log_name' => 'category',
            'attributes' => [
                'title',
                'slug',
                'description',
                'content',
                'translation_status',
                'author_id',
                'author_type',
            ],
        ],
    ],
    'hooks' => [
        Category::class => [
            'deleting' => [
                'handler' => 'categorizables_detached',
                'log_name' => 'category',
                'entry_type' => 'log',
                'event' => 'categorizables_detached',
                'description' => 'categorizables_detached',
            ],
        ],
    ],
    'filament' => [
        CategoryResource::class => [
            'owner_model' => Category::class,
            'aggregate_subjects' => [
                CategoryTranslation::class => 'translations',
            ],
        ],
    ],
],
```

### 2. Register in the ServiceProvider

```php
use Moox\Audit\Support\AuditPackageRegistry;

// in packageBooted():
if (class_exists(AuditPackageRegistry::class) && config('audit.enabled', true)) {
    AuditPackageRegistry::register('category', config('category.audit', []));
}
```

The `class_exists` guard keeps your package usable without `moox/audit` installed.

### 3. Filament activity tabs

When `filament` config is registered, `AuditBootstrap` automatically adds the **Activity** relation manager to the resource.

Alternatively, merge relations manually (loose coupling via `class_exists`):

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

## Presets

Built-in presets in `config/audit.php`:

| Preset | Purpose |
| --- | --- |
| `draft_main` | Main draft entities — tracks `created`, `updated`, `deleted`, `restored` as `audit` entries |
| `draft_translation` | Translation rows — same events, adds `locale` to properties, hides internal `*_by_*` morph columns |

Override or add presets in published config. Per-model config can set `'preset' => 'draft_main'` (package or app level).

## Entry types

| Type | Use case |
| --- | --- |
| `audit` | Attribute changes on configured models (default for presets) |
| `log` | Custom events via hooks or `MooxActivityLogger::log()` |

The global `AuditResource` provides tabs to filter by entry type.

## Hooks

Hooks log custom events outside standard CRUD tracking. Register them under `audit.hooks` in package or app config:

```php
'hooks' => [
    MyModel::class => [
        'deleting' => [
            'handler' => 'categorizables_detached', // built-in handler
            'log_name' => 'category',
            'entry_type' => 'log',
            'event' => 'categorizables_detached',
            'description' => 'categorizables_detached',
        ],
    ],
],
```

Without a `handler`, the hook falls back to `MooxActivityLogger::log()` with the given options.

## App overrides

Published `config/audit.php` sections `models`, `hooks`, and `filament` override package defaults.

Disable a model:

```php
'models' => [
    Category::class => ['enabled' => false],
],
```

Replace the attribute list:

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

Register app-only models (no package config required):

```php
'models' => [
    \App\Models\Post::class => [
        'log_name' => 'posts',
        'attributes' => ['title', 'status'],
        'events' => ['created', 'updated', 'deleted'],
    ],
],
```

## Manual logging

For one-off or programmatic entries, use `MooxActivityLogger`:

```php
use Moox\Audit\Services\MooxActivityLogger;

MooxActivityLogger::log('my-channel', 'Something happened', [
    'entry_type' => 'log',
    'event' => 'exported',
    'subject' => $model,
    'properties' => ['format' => 'csv'],
    'scope' => 'default',
]);
```

The causer defaults to the authenticated user, or `audit.system_causer` when no user is logged in.

## Filament UI

### AuditPlugin

Register in your panel provider (the install command does this automatically):

```php
use Moox\Audit\Plugins\AuditPlugin;

$panel->plugins([
    AuditPlugin::make(),
]);
```

`AuditPlugin` registers the global `AuditResource` and boots `AuditBootstrap`.

### Activity relation manager

On edit/view pages of configured resources, the **Activity** tab lists related entries. When `aggregate_subjects` is set, activities from related models (e.g. translations) are included in a single timeline.

## Configuration reference

| Key | Description |
| --- | --- |
| `enabled` | Global switch (`AUDIT_ENABLED` env, default `true`) |
| `activity_model` | Eloquent model class (default `Moox\Audit\Models\Activity`) |
| `system_causer` | Model class used as causer when no user is authenticated |
| `default_entry_type` | Default entry type for model audits (default `audit`) |
| `user_models` | Map of user model classes to `title_attribute` and `label` for property enrichment |
| `presets` | Named preset blocks merged into per-model config |
| `models` | App-level model overrides |
| `hooks` | App-level hook overrides |
| `filament` | App-level Filament resource overrides |
| `retention` | Retention policy placeholders (`live`, `archive`, `backup` per entry type) |
| `resources.audit` | `AuditResource` labels and list tabs |
| `navigation_group` | Filament navigation group for `AuditResource` |

## Database

The migration creates an `activity_log` table extending Spatie's schema with Moox columns:

| Column | Purpose |
| --- | --- |
| `entry_type` | `audit` or `log` |
| `scope` | Optional scope identifier (indexed) |
| `attribute_changes` | JSON diff of tracked attribute changes |

Spatie's `subject` / `causer` morphs, `event`, `properties`, and `log_name` are used as usual.

## Manual Installation

```bash
php artisan vendor:publish --tag="audit-migrations"
php artisan migrate
php artisan vendor:publish --tag="audit-config"
```

Then register `AuditPlugin::make()` in your Filament panel provider.

## Testing

```bash
composer test
# or from the monorepo root:
php vendor/bin/pest --configuration=packages/audit/phpunit.xml packages/audit/tests/Unit
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
