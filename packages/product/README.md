# Moox Product

Sellable product entity for Moox CMS: commerce fields on the main record, translatable content, draft/publish workflow, and a Filament admin UI.

## Data model

### Table `products`

| Field | Type | Notes |
|-------|------|-------|
| `sku` | string, unique | Required business key |
| `type` | string | Default `simple` — values from `config('product.types')` |
| `status` | string | Default `draft` — values from `config('product.statuses')` |
| `price`, `sale_price`, `cost_price` | decimal | Price fields |
| `stock`, `stock_min` | int | Inventory |
| `weight`, `weight_unit`, `unit_of_measure` | mixed | Weight / units |
| `is_purchasable`, `is_sellable` | bool | Flags |
| `custom_properties` | json | Arbitrary key-value data |
| `uuid`, `ulid` | uuid/ulid | Identifiers |
| `deleted_at` | timestamp | Soft delete |

No `created_at`/`updated_at` on the main table (Moox draft pattern).

### Table `product_translations`

Per locale: `name`, `slug`, `short_description`, `description`, `meta_title`, `meta_description`, plus the Moox translation workflow (`translation_status`, publish timestamps, actor morphs).

### Table `product_assignments`

Morph pivot for assignments to other entities (e.g. product groups). Columns: `is_primary`, `sort_order`. **The relation is not defined in the package** — only the table and pivot model.

## What this package provides

- Filament `ProductResource` with form and table for all product fields
- Multilingual content via `moox/localization` + Astrotomic Translatable
- Slugs via `moox/slug` (`TitleWithSlugInput`)
- Draft tabs (all / deleted) via Moox Core
- Taxonomy hook (`HasModelTaxonomy`) — `taxonomies` empty in package config; app can extend
- Factory, seeder, Pest tests
- Configurable product types, statuses, display currency (`config/product.php`)
- Relations to other Moox entities via app config + `HasResourceRelations` (e.g. product groups)

## What this package does not provide

- **No product group relation out of the box** — app must define `config/product.php` → `relations`
- **No brands** — no `brand_id`, no brand model
- **No EAN/MPN/barcode fields**
- **No price lists, tax rules, or currency conversion** — only a `currency` config value for Filament display
- **No import/transform logic** — use `moox/transform` + app migrations
- **No storefront URLs** — `urlPathEntityType: 'products'` is hardcoded in the resource
- **No custom product types** (e.g. `web`/`offline`) in the package — app extends `config/product.php` and lang files

## Installation

```bash
composer require moox/product
php artisan vendor:publish --tag=moox-product-config
php artisan migrate
```

Register the Filament plugin in your app: `Moox\Product\Plugins\ProductPlugin`.

## App configuration (example)

The package ships defaults. Project-specific settings belong in the **app**:

```php
// config/product.php
'types' => [
    // package defaults plus e.g.:
    'web' => 'trans//product::product.type_web',
    'offline' => 'trans//product::product.type_offline',
],

'relations' => [
    'product_assignments' => [
        // morph pivot to ProductGroup
    ],
],
```

## Dependencies

- `moox/core`
- `moox/localization`
- `moox/slug`

## Tests

```bash
php artisan test --compact packages/product/tests
```
