# Address Attributes

This document describes the database fields and pivot attributes of the Moox Address entity.

## The Address Model

The `Address` model (`Moox\Address\Models\Address`) stores normalized postal data. It extends `BaseItemModel`, uses soft deletes, and supports taxonomies via `HasModelTaxonomy`.

### Attributes

#### Base Fields

-   `label` (string, 120) - Optional internal label (e.g. Headquarter, Warehouse)
-   `name` (string, 160) - Recipient or company name on the address
-   `street` (string, 160) - Street and house number
-   `street2` (string, 160) - Additional address line (suite, building, etc.)
-   `postal_code` (string, 20) - Postal / ZIP code
-   `city` (string, 120) - City
-   `state` (string, 120) - State, region, or province
-   `country_code` (string, 2) - ISO 3166-1 alpha-2 country code (stored uppercase)
-   `is_primary` (boolean) - Marks this address as primary (default: false)
-   `data` (json) - Flexible key-value payload for custom metadata
-   `deleted_at` (datetime) - Soft-delete timestamp
-   `created_at` (datetime) - Creation timestamp
-   `updated_at` (datetime) - Last update timestamp

Validation rules live in `Moox\Address\Support\AddressRules` and are applied in the Filament resource.

#### Duplicate detection

Duplicate addresses are blocked on save via `DuplicateAddressException`. Comparison uses `AddressFingerprint` with these columns only:

-   `street`
-   `street2`
-   `postal_code`
-   `country_code`

Not part of the fingerprint: `label`, `name`, `city`, `state`, `is_primary`, and `data`. Two records with the same street, postal code, and country but different city or name are still treated as duplicates.

Empty strings are normalized to `null` before comparison. `country_code` is trimmed and uppercased on save.

### Methods

#### Formatting

-   `formattedLine()` - Single-line summary: name, street lines, postal code + city, country code

#### Duplicate detection

-   `scopeWithFingerprint()` - Query scope for fingerprint lookup

### Relationships

-   `addressables()` - Pivot rows linking this address to owners

## The Addressable Pivot

Assignments between an address and an owner (company, contact, user, etc.) live on `addressables`, not on `addresses`. Roles are pivot flags, not columns on the address itself.

### Attributes

#### Pivot Fields

-   `addressable_type` (uuid morph) - Owner model class
-   `addressable_id` (uuid) - Owner primary key
-   `address_id` (foreignId) - References `addresses.id` (cascade on delete)
-   `billing_address` (boolean) - Use as billing address (default: false)
-   `postal_address` (boolean) - Use as postal address (default: false)
-   `delivery_address` (boolean) - Use as delivery address (default: false)
-   `created_at` (datetime) - Creation timestamp
-   `updated_at` (datetime) - Last update timestamp

Unique constraint: `(addressable_type, addressable_id, address_id)`.

### Methods

-   `activeRoles()` - Active role keys: `billing`, `postal`, `delivery`

### Relationships

-   `addressable()` - Owner (`MorphTo`)
-   `address()` - Linked `Address` (`BelongsTo`)

### Owner trait

Models that can own addresses use `Moox\Address\Concerns\HasAddresses`:

-   `addresses()` - `MorphToMany` via `addressables`, with pivot columns from `config('address.relations.addressables')`

Register allowed owner types under `address.relations.addressables.owner_types` in `config/address.php`.

## Translations

Field labels for the admin UI are in `resources/lang/{locale}/fields.php` (e.g. `address::fields.street`). Entity titles use `address::address.*`.

There are no translatable model attributes on `Address`; all address fields are stored on the main table.
