---
name: moox-audit
description: >-
  Integrates moox/audit activity logging into Moox consumer packages (config,
  ServiceProvider, Filament, hooks, tests). Use when the user says moox/audit,
  moox-audit, protokolieren, auditieren, activity log, or names a package to
  audit (e.g. moox/tag, tag package).
---

# Moox Audit — package integration

Config-driven activity logging via `moox/audit`. Consumer packages declare an `audit` block in package config and register it in the ServiceProvider. **No model traits** and **no Eloquent changes** on models for standard CRUD tracking.

Canonical package docs: [packages/audit/README.md](../../../packages/audit/README.md)

Gold-standard consumer: `packages/category` (`config/category.php`, `CategoryServiceProvider`).

## When to use

| User intent | Action |
| --- | --- |
| "Nutze skill moox/audit und protokolieren moox/tag" | Follow [integration.md](integration.md) for `packages/tag` |
| "Audit für {package}" / "protokolieren {package}" | Same workflow for `packages/{package}` |
| Install audit in an app | `composer require moox/audit` + `php artisan mooxaudit:install` (see package README) |
| App-only model tracking | Published `config/audit.php` `models` section (no package registry) |
| Custom lifecycle event | Hook or `MooxActivityLogger::log()` — see [decisions.md](decisions.md) |

## Integration workflow (checklist)

Copy and track:

```
- [ ] 1. Inspect target package (models, translations, resource, delete side-effects)
- [ ] 2. Add `audit` block to `packages/{pkg}/config/{pkg}.php`
- [ ] 3. Register in `{Pkg}ServiceProvider::packageBooted()` via AuditPackageRegistry
- [ ] 4. Decide hooks (pivot detach on delete?) — decisions.md
- [ ] 5. Add/adjust Pest tests when behavior is new or non-trivial
- [ ] 6. Run package tests + `composer lint` / `composer analyse` if touched from monorepo root
```

**Do not** add `moox/audit` to the consumer package's `composer.json` `require`. Use `class_exists(AuditPackageRegistry::class)` so the package works without audit installed.

## Quick reference

### ServiceProvider registration

```php
use Moox\Audit\Support\AuditPackageRegistry;

// in packageBooted():
if (class_exists(AuditPackageRegistry::class) && config('audit.enabled', true)) {
    AuditPackageRegistry::register('{package_key}', config('{package_key}.audit', []));
}
```

`{package_key}` matches the config file name (`category`, `tag`, …).

### Config shape

Three sections under `{package}.audit`:

| Section | Purpose |
| --- | --- |
| `models` | Per-model tracking: preset, `log_name`, `attributes` |
| `hooks` | Custom events (e.g. pivot detach on `deleting`) |
| `filament` | Activity tab on resources + `aggregate_subjects` for translations |

Presets (`draft_main`, `draft_translation`) live in `config/audit.php`. See [decisions.md](decisions.md) for attribute selection and hooks.

### Filament

When `filament` is registered, `AuditBootstrap` adds the **Activity** relation manager automatically. No manual `getRelations()` change unless you need loose coupling — see package README.

## Tests

After integrating audit into a consumer package, add tests when you introduce hooks or non-default config. Run:

```bash
php vendor/bin/pest --configuration=packages/{pkg}/phpunit.xml packages/{pkg}/tests
```

Audit package tests (reference):

```bash
php vendor/bin/pest --configuration=packages/audit/phpunit.xml packages/audit/tests/Unit
```

## Additional resources

- Step-by-step templates and tag example: [integration.md](integration.md)
- Presets, attributes, hooks, entry types: [decisions.md](decisions.md)
