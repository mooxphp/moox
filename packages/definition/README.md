# moox/definition

Canonical, framework-independent definitions for Moox packages, plus optional Laravel and Filament projections.

`moox.json` remains the conceptual source of truth. This package does not parse it and it does not implement moox Builder. Definitions are written in PHP and resolved in PHP.

The definition model depends only on PHP. Laravel and Filament are suggested dependencies of the projections. Projection classes import those frameworks, but installing `moox/definition` does not install them.

## Boundaries

| Layer | Responsibility |
| --- | --- |
| Plain PHP resolution | Validate fields, relations, and features. Compose a resolved entity without mutating the declaration. |
| Laravel runtime | Apply a blueprint, configure an Eloquent model, generate declared identifiers, and enforce write ownership. |
| Filament runtime | Build native form components and table columns from the resolved entity. |
| Generated artifacts | Not produced automatically. A migration class, model class, and Filament resource class remain the framework types those tools discover. |

A projection reads the resolved entity. It does not own a second field list.

## Resolution

A package declares entities. An entity declares domain fields, relations, features, constraints, and capabilities. `Catalog::resolve()` copies that declaration, applies features in declaration order, then applies domain fields.

Feature composition is deterministic:

- The same declaration resolves to the same field order and attributes.
- A feature field and a domain field with the same name are a conflict.
- Two features that contribute the same name are a conflict.
- `Entity::override('field')` is the explicit replacement. An override that replaces nothing is also a conflict.
- Being contributed by a feature does not make a field system-managed.

Identity is the implemented feature. Its canonical fields are `id`, `ulid`, and `uuid`, with primary allocation, ULID generation, UUID generation, uniqueness, immutability, and system-managed write ownership declared on those fields.

## Projections

Laravel migration and model projections understand the semantic types whose storage is fully determined. `dateTime` is a UTC timestamp. A `belongsTo` foreign key uses the storage of the referenced field. It does not assume `id`.

Filament builds native components. Types without a native projection throw `UnsupportedProjection` and name the field and type.

```php
$projection = new \Moox\Definition\Filament\Resource($resolved);

$projection->field('article_number')
    ->formComponent()
    ->required()
    ->maxLength(32);

$projection->field('article_number')
    ->replaceForm(\Filament\Forms\Components\TextInput::make('article_number')->password());

$projection->field('article_number')
    ->tableColumn()
    ->sortable();
```

`formComponent()` and `tableColumn()` return the Filament component. Further calls are the Filament API. Customizations stay on that projection instance. Another `Resource` built from the same resolved entity has its own components. The resolved entity is not modified.

## Framework classes you still write

Panels, Eloquent, and the migrator discover concrete classes. Those classes stay thin and delegate.

- A model extends `Moox\Definition\Laravel\Model` and implements `definition(): ResolvedEntity`. Relation model classes are returned from `modelBindings()`. Missing bindings are reported when the relation is used. They are not guessed from names.
- A migration's `up()` creates a table and calls `Moox\Definition\Laravel\Migration::apply()`. Pivot tables are applied separately with `applyPivot()`.
- A Filament resource extends `Filament\Resources\Resource`. Its `form()` and `table()` construct a new definition resource and call `applyForm()` or `applyTable()`. Create that projection inside the method, or in a method called by both, so instances are not reused across requests.
- List, create, and edit screens remain Filament page classes. `Moox\Definition\Filament\Page` only forwards the same projection. It is not a base page.

Customizations belong next to that concrete resource, on the projection's native components.

## Dependencies

| Use | Required packages |
| --- | --- |
| Definitions, features, validation, resolution | PHP `^8.3` |
| Migration and model projections | `laravel/framework` `^13.0` |
| Filament projections | `filament/filament` `^5.6` |

Verified with this package's development dependencies: Laravel 13.34, Filament 5.9, Pest 4.7, and Testbench 11.

## Tests

This package is tested on its own. Install its development dependencies inside the package, then run Pest. Laravel projections boot through Orchestra Testbench; no host application is required.

```bash
composer install
composer test
```

## Not in this package

See [PLAN.md](PLAN.md) and [DECISIONS.md](DECISIONS.md). Repository access, Audit, Publishing, executable `moox.json`, and moox Builder are intentionally absent.
