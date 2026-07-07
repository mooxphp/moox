# Moox ProductGroup

Product family / grouping entity: hierarchical groups with `parent_id`, translatable content, draft/publish workflow, and a Filament admin UI.

## Data model

### Table `product_groups`

| Field | Type | Notes |
|-------|------|-------|
| `code` | string, unique | Required business key |
| `type` | string | Default `family` — values from `config('productgroup.types')` |
| `status` | string | Default `draft` |
| `parent_id` | bigint, nullable | Simple parent reference |
| `_lft`, `_rgt` | int | Nested-set columns (Kalnoy) — **for programmatic imports only**, not in Filament UI |
| `sku_prefix` | string, nullable | Optional SKU prefix |
| `custom_properties` | json | Arbitrary key-value data |
| `uuid`, `ulid` | uuid/ulid | Identifiers |
| `deleted_at` | timestamp | Soft delete |

### Table `product_group_translations`

Per locale: `name`, `slug`, `short_description`, `description`, `meta_title`, `meta_description`, plus the Moox translation workflow.

## What this package provides

- Filament `ProductGroupResource` for all visible fields
- Parent selection via `parent_id` (displays parent `code`)
- Simple `parent()` / `children()` relations on `parent_id`
- Nested-set columns in DB + model (`_lft`/`_rgt` fillable) for external imports
- Multilingual content, slugs, draft tabs like other Moox entities
- Morph pivot assignment to products via app config (`config/productgroup.php` → `relations`)
- Factory, seeder, Pest tests

## What this package does not provide

- **No nested-set behavior on the model** — no `NodeTrait`, no select-tree in Filament (`filament-select-tree` is in `composer.json` but unused)
- **No tree UI** — hierarchy is a flat `parent_id` select, not a tree widget
- **`_lft`/`_rgt` not in Filament** — written only via code/transform, not manually editable
- **No brands** — no `brand_id`
- **No attribute sets** — removed
- **No default unit field** — removed
- **No product relation out of the box** — app must define `relations.product_assignments` in `config/productgroup.php`
- **No import/transform logic** — app responsibility

## Installation

```bash
composer require moox/productgroup
php artisan vendor:publish --tag=moox-productgroup-config
php artisan migrate
```

Filament plugin: `Moox\ProductGroup\Plugins\ProductGroupPlugin`.

## App configuration (example)

```php
// config/productgroup.php
'relations' => [
    'product_assignments' => [
        'kind' => 'morph_pivot',
        'relationship' => 'products',
        // ...
    ],
],
```

Product ↔ ProductGroup requires **both** app configs (`config/product.php` and `config/productgroup.php`).

## Dependencies

- `moox/core`
- `moox/localization`
- `moox/slug`
- `kalnoy/nestedset` (migration columns only)
- `codewithdennis/filament-select-tree` (declared, currently unused)

## Tests

```bash
php artisan test --compact packages/productgroup/tests
```
