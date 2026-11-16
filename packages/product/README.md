# Moox Product

Product entity for a product category. The type on the product decides which fields apply. A simple product may have variants.

## Tables

`products` follows the product category record: identity, active, sort, external fields, media, data, audit, archive, and publish.

Type-specific columns live on the same row:

- `simple` has `sku` and `is_orderable`
- `grouped` has `is_canonical` and, for a reference, `canonical_product_id`
- `bundle`, `configurable`, and `custom` are further type values

`product_category_id` and `manufacturer_id` have no foreign key. The app wires each one with `belongs_to` and chooses the model in config.

`product_translations` has `title`, `slug`, and `description`, without timestamps. The slug is unique per locale inside the same product category. The model checks that on save.

`variants` belongs to exactly one simple product. Empty column values (sku, external fields, note, image, data, publish) and empty translation fields are read from that product. `is_orderable` and manufacturer fields do not inherit. Filament fills inherited columns via `mergeInheritedAttributesForForm`. Builder field groups are assigned to the `variant` entity in moox/builder. Empty builder values inherit from the simple product through `InheritsCustomFields`; equal values are not stored again.

`variant_translations` mirrors product translations with nullable title, slug, and description.

## Relations

`canonicalProduct` points at another product. `variants` is a has-many on a simple product. `product_category` and `manufacturer` are config relations, for example:

```php
'product_category' => [
    'kind' => 'belongs_to',
    'presentation' => 'hidden',
    'model' => ProductCategory::class,
    'foreign_key' => 'product_category_id',
],
```
