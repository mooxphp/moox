![Moox Company](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox Company

ERP entity for company master data: customers, suppliers, partners, subsidiaries, and internal organizational units. The package follows the Moox greenfield approach: **no payment fields**, **no `employee_id`**, **no default-address foreign keys** — addresses, employee assignments, and commercial terms are linked via morph pivots (`addressables`, `employee_assignments`, `commercial_term_assignments`).

## Features

- UUID primary key, soft deletes, flexible JSON `data` field
- Hierarchy via `parent_id` (parent company / subsidiary)
- Config-driven statuses, company types, Filament tabs, and scopes
- Addresses via `moox/address` and the `addressables` pivot (billing, postal, delivery roles)
- Filament resource with list tabs, filters, and a relation manager for subsidiaries
- Factory with states (`customer`, `supplier`, `withParent`, …) and Pest tests

## Responsibility Boundaries

- `moox/company` owns company master data, hierarchy, and company-focused UI.
- `moox/address` integration is optional and configured via `company.morph_relations.addressables`.
- The package should not assume concrete address classes in model code; relation targets come from config.
- Address ownership registration remains in `config/address.php` (`owner_types`).

## Requirements

| Package     | Purpose                                      |
|------------|-----------------------------------------------|
| `moox/core` | Base model, Filament resource, morph pivots   |
| `moox/data` | Moox data integration                         |

For addresses in the admin and API you also need **`moox/address`**, wired in your app config (see [Addresses](#addresses-mooxaddress)).

## Installation

```bash
composer require moox/company
php artisan moox:install
```

The migration creates the `companies` table. Optionally publish configuration:

```bash
php artisan vendor:publish --tag=company-config
```

## Registering with Filament

The package registers via `CompanyPlugin`. In your panel provider (e.g. `MooxPanelProvider`):

```php
use Moox\Company\Plugins\CompanyPlugin;

$panel->plugins([
    CompanyPlugin::make(),
]);
```

`CompanyPlugin` uses `ChildResourceRegistrar` to register the resource together with tabs, scopes, and morph relations from `config('company.resources.company')`.

## The Company Model

Class: `Moox\Company\Models\Company`  
Extends `Moox\Core\Entities\Items\Item\BaseItemModel`.

### Traits

| Trait | Purpose |
|-------|---------|
| `HasUuids` | String UUID as `id` |
| `SoftDeletes` | Soft delete |
| `HasModelTaxonomy` | Taxonomies from `config('company.taxonomies')` |
| `HasMorphPivotRelations` | Dynamic morph-pivot relations (e.g. addresses) |

### Fields

#### Identity & type

| Field | Type | Description |
|-------|------|-------------|
| `status` | string(30) | e.g. `draft`, `active`, `inactive`, `approved`, `archived` (from config) |
| `name` | string(120) | Short / internal name |
| `display_name` | string(120) | Display name in UI and selects |
| `legal_name` | string(120) | Legal name |
| `company_type` | string(30) | `customer`, `supplier`, `partner`, `prospect`, `internal` |
| `note` | text | Free text |
| `search_terms` | text | Additional search terms |
| `external_reference` | string(100) | External ID (ERP, CRM, …) |

#### Hierarchy

| Field | Type | Description |
|-------|------|-------------|
| `parent_id` | uuid (FK) | Parent company; `null` = no parent |
| `is_fully_owned_subsidiary` | boolean | Fully owned by the group |

On save, `parent_id` equal to the record’s own id is cleared to `null`.

#### Contact & tax

| Field | Type | Description |
|-------|------|-------------|
| `phone`, `fax` | string(30) | Phone / fax |
| `email` | string(100) | Email |
| `url` | string(255) | Website |
| `tax_number`, `vat_number` | string(30) | Tax / VAT ID |
| `has_no_vat_number` | boolean | Disables the VAT field in the form |

#### Settings

| Field | Type | Description |
|-------|------|-------------|
| `default_currency_code` | char(3) | ISO currency, normalized to uppercase (default: `EUR`) |
| `partner_type`, `partner_id` | int | Optional reference to an external partner system |
| `language_id` | FK | `static_languages` |
| `localization_id` | FK | `localizations` |
| `sort` | int | Manual sort order |
| `is_active` | boolean | Active flag |
| `approved_at` | datetime | Approval timestamp |
| `approved_by_type`, `approved_by_id` | morph | Who approved the record |
| `no_marketing_action` | boolean | No marketing |
| `no_marketing_action_reason` | string(255) | Reason (visible when marketing is off) |
| `data` | json | Arbitrary extra data |

Validation rules live in `Moox\Company\Support\CompanyRules` and are used in the Filament resource.

### Methods

- **`displayLabel()`** — Display text: `display_name` → `name` → `legal_name` → UUID
- **`getResourceName()`** — Slug `company` for Moox Core

### Eloquent relationships

```php
$company->parent;       // BelongsTo<Company>
$company->children;     // HasMany<Company>
$company->approvedBy;  // MorphTo

$company->addresses();  // MorphToMany via addressables pivot (all)
$company->address();    // “Primary” address(es) per pivot config
```

Taxonomy methods are resolved dynamically from `config('company.taxonomies')` (e.g. `$company->tags()`).

## Usage

### Creating a company

```php
use Moox\Company\Models\Company;

$customer = Company::create([
    'status' => 'active',
    'name' => 'Acme GmbH',
    'display_name' => 'Acme GmbH',
    'company_type' => 'customer',
    'default_currency_code' => 'EUR',
    'is_active' => true,
]);

echo $customer->displayLabel(); // "Acme GmbH"
```

### Factory

```php
Company::factory()->customer()->create();
Company::factory()->supplier()->draft()->create();
Company::factory()->inactive()->create();

$parent = Company::factory()->create();
Company::factory()->withParent($parent)->create();
```

### Corporate hierarchy

```php
$holding = Company::factory()->create(['company_type' => 'internal']);
$subsidiary = Company::factory()->withParent($holding)->create();

$holding->children;   // Collection of subsidiaries
$subsidiary->parent;  // Holding company
```

### Queries

```php
Company::query()
    ->where('company_type', 'supplier')
    ->where('is_active', true)
    ->get();

Company::query()->where('status', 'approved')->get();
```

## Addresses (`moox/address`)

Addresses are **not** stored as columns on `companies`. They are linked through the `addressables` pivot table:

| Pivot column | Meaning |
|--------------|---------|
| `billing_address` | Billing address |
| `postal_address` | Postal address |
| `delivery_address` | Delivery address |

### App configuration

In **`config/company.php`** (after publish), set `morph_relations.addressables` with model and pivot — for example in this app:

```php
'morph_relations' => [
    'addressables' => [
        'model' => \Moox\Address\Models\Address::class,
        'pivot_model' => \Moox\Address\Models\Addressable::class,
        // ...
    ],
],
```

In **`config/address.php`**, register the company as an allowed owner:

```php
'owner_types' => [
    \Moox\Company\Models\Company::class => 'Company',
],
```

### Assigning an address (Eloquent)

```php
$company->addresses()->attach($address->id, [
    'billing_address' => true,
    'postal_address' => true,
    'delivery_address' => false,
]);

$company->address; // Collection of address(es) marked as primary
```

In Filament, address management appears as a **morph-pivot relation manager** (Moox Core) once `moox/address` is installed and configured.

## Filament admin

Resource: `Moox\Company\Resources\CompanyResource`

| Page | Route (relative) | Description |
|------|------------------|-------------|
| List | `/` | Table with tabs, filters, bulk actions |
| Create | `/create` | Form; `?parent_id=` pre-fills the parent company |
| Edit | `/{record}/edit` | Master data |
| View | `/{record}` | Read-only view |

### List tabs

Tabs come from `config('company.resources.company.tabs')`. Typical app defaults include:

- **All** — not soft-deleted
- **Suppliers** / **Customers** — filter on `company_type`
- **Active** — `is_active = true`
- **Deleted** — soft-deleted records

### Subsidiaries

`ChildrenRelationManager` lists `children` of the current company. “Create” opens the create page with `parent_id` pre-filled:

```php
CompanyResource::getUrl('create', ['parent_id' => $company->getKey()]);
```

### Scopes (cross-entity)

Under `resources.company.scopes` you can attach other Moox resources (News, Media, Tags, User, …) to companies — see your published `config/company.php`.

## Configuration

File: `config/company.php`

| Key | Description |
|-----|-------------|
| `readonly` | Make the resource read-only |
| `statuses` | Allowed status values (validation + selects) |
| `company_types` | Allowed company types |
| `default_currency_code` | Default in the form |
| `resources.company` | Labels, tabs, scopes |
| `relations` | Labels for parent / children |
| `morph_relations` | Pivot definitions (addresses, …) |
| `taxonomies` | Taxonomy integration |
| `navigation_group` | Filament navigation group (e.g. `Portal`) |

Changes to `statuses` or `company_types` are picked up automatically in `CompanyRules` and Filament selects/filters.


## Running tests

From the package directory:

```bash
composer test
```

Or from the monorepo root:

```bash
php vendor/bin/pest --configuration=packages/company/phpunit.xml packages/company/tests
```

## Translations

- Entity titles: `resources/lang/{locale}/company.php`
- Field labels: `resources/lang/{locale}/fields.php` (DE and EN included)

## See also

- [Moox Address](../address/README.md) — address model and `addressables` pivot
- [Moox documentation](https://moox.org/docs/company)
- [Moox installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md)

## License

MIT — see [Moox License](https://github.com/mooxphp/moox/blob/main/LICENSE.md).
