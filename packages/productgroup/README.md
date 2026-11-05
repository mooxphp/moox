# Moox ProductGroup

Hierarchical product family. Groups have a parent, translatable title, slug and description, and a Filament admin UI. A group is not orderable.

## Tables

### `product_groups`

| Field | Type | Notes |
|-------|------|-------|
| `ulid`, `uuid` | ulid / uuid | Unique identifiers |
| `parent_id` | bigint, nullable | Parent group. `nullOnDelete` |
| `lft`, `rgt` | unsigned int, default 0 | Stored integers. Editable in Filament. Not a nested set |
| `is_active` | boolean, default true | |
| `sort_order` | unsigned int, default 0 | |
| `external_reference`, `external_info`, `external_status` | string, nullable | |
| `note` | text, nullable | |
| `image` | json, nullable | Moox Media picker |
| `data` | json, nullable | |
| audit columns | timestamps + actor id/type | created, updated, archived, deleted, restored, published, unpublished |

Indexes: `(parent_id, lft)`, `(parent_id, sort_order)`.

### `product_group_translations`

No timestamps. One row per group and locale.

| Field | Type | Notes |
|-------|------|-------|
| `product_group_id` | bigint | Cascade on delete |
| `locale` | string | |
| `title`, `slug` | string | Required |
| `description` | text, nullable | |

Unique: `(product_group_id, locale)`.

Slug uniqueness among siblings is not a SQL unique index. `ProductGroupTranslation` rejects a save when another group with the same parent and the same locale already uses that slug. Groups with `parent_id` null are siblings of each other.

## What this package provides

- Filament `ProductGroupResource` (list, create, view, edit)
- Parent select on `parent_id`. The current group and its descendants are excluded
- `parent()` and `children()` on `parent_id`
- Translatable `title`, `slug`, `description`, with translation fallback
- Media through the `image` JSON field, `media_usables`, and a `preview` conversion
- Active, identity, external, data, audit, archive, publish and soft-delete sections
- Factory, seeder, Pest tests
- Filament plugin `Moox\ProductGroup\Plugins\ProductGroupPlugin`

## What this package does not provide

- No nested-set library and no tree widget. Hierarchy is `parent_id` only
- No product, article or category relation on the model. The app wires shop products in `config/productgroup.php` as the `product_assignments` morph pivot when `moox/product` is installed
- No brands, attribute sets or SKU fields

## Installation

```bash
composer require moox/productgroup
php artisan vendor:publish --tag=moox-productgroup-config
php artisan migrate
```

Register `ProductGroupPlugin::make()` on the Filament panel.

## Dependencies

- `moox/core`
- `moox/localization`
- `moox/media`
- `moox/support`

## Tests

```bash
php artisan test --compact packages/productgroup/tests
```
