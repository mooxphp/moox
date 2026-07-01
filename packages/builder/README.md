# Moox Builder

Runtime field groups for Filament resources — ACF-like, but pure Laravel/Filament.

Admins define fields in the panel. Values are stored in typed `builder_field_values` rows (no JSON blob on the model, no WordPress/postmeta).

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [The Two Layers](#the-two-layers)
3. [Database](#database)
4. [Runtime Flow](#runtime-flow)
5. [Installation](#installation)
6. [Connect a Resource](#connect-a-resource)
7. [Field Groups in Admin](#field-groups-in-admin)
8. [Field Types & Capabilities](#field-types--capabilities)
9. [Translations](#translations)
10. [Configuration](#configuration)
11. [Extension](#extension)
12. [Model API](#model-api)
13. [API Serialization](#api-serialization)
14. [Package Structure](#package-structure)
15. [Testing](#testing)
16. [Limits & Roadmap](#limits--roadmap)

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         ADMIN (Definition)                              │
│  Filament → Fields → Field Groups (FieldGroupResource)                  │
│       ↓                                                                 │
│  builder_field_groups / builder_fields / builder_field_options          │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                          DefinitionRegistry (Cache)
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      RUNTIME (Consumer Resources)                       │
│  ItemResource + HasCustomFields                                         │
│       ↓                                                                 │
│  EntityRegistry → LocationMatcher → SchemaCompiler → Filament Sections  │
│       ↓                                                                 │
│  PersistCustomFields (RecordSaved) → CustomFieldsManager                │
│       ↓                                                                 │
│  builder_field_values (TypedValueColumns)                               │
└─────────────────────────────────────────────────────────────────────────┘
```

**Core principle:** Definition and storage are strictly separated.

| Layer | Question | Where |
|-------|----------|-------|
| **Definition** | Which fields exist? | `builder_field_*` tables + admin UI |
| **Storage** | Where are values stored? | `builder_field_values` + `TypedValueColumns` |

---

## The Two Layers

### 1. Definition Layer

Responsible for **what** is shown and **where** (location).

| Component | Role |
|-----------|------|
| `FieldGroupResource` | Filament CRUD for field groups |
| `FieldGroupPersistence` | Saves groups, fields, options, location rules, nested fields; migrates JSON values when nested subfields are renamed or removed |
| `FieldGroupValidator` | Checks duplicate **storable** field keys (globally per group, including tabs) and conflicts between groups |
| `DefinitionRegistry` | Loads active groups, caches as arrays |
| `LocationMatcher` | Matches `location_rules` against `LocationContext` |
| `EntityRegistry` | Finds Filament resources with `HasCustomFields` → entity keys |
| `SchemaCompiler` | Builds Filament sections, tabs, and layout fields from definitions |

Definitions are transported as **DTOs** (`FieldGroupDefinition`, `FieldDefinition`) — not as loose Eloquent models in the runtime path.

### 2. Storage Layer

Responsible for **values** per record.

| Component | Role |
|-----------|------|
| `TypedValueColumns` | Maps field type → DB column (`value_string`, `value_json`, …) |
| `CustomFieldsManager` | Load/save for resources, option validation, hydration cache |
| `FieldValueValidator` | Validates nested values (repeater, group, flexible content) |
| `FieldValuePurger` | Deletes values when fields/groups change (root fields) |
| `CompoundFieldValueMigrator` | Renames/removes nested keys in `value_json` (group, repeater, flexible content) |
| `PersistCustomFields` | Listener on Filament `RecordSaved` |
| `BuilderMediaUsageSync` | Maintains `media_usables` for image/gallery/file fields |
| `BuilderFieldValueMediaMetadataSync` | Updates media snapshots in `value_json` when metadata changes |

Values are **not** attached to the Eloquent model (no `custom_fields` JSON column needed). Media fields store **references** in `value_json`, not in model columns.

---

## Database

### `builder_field_groups`

| Column | Meaning |
|--------|---------|
| `name` | Display name (= section title in the form) |
| `slug` | Technical group key |
| `location_rules` | JSON: where the group appears (see below) |
| `placement` | Reserved (`default`) |
| `settings` | Reserved for group settings |
| `sort` | Order of multiple groups |
| `active` | Only active groups are rendered |

### `builder_fields`

Fields of a group: `name`, `label`, `type`, `config`, `validation`, `sort`.

**`parent_field_id`** links subfields to layout fields (group, repeater, flexible content layouts). The tree is synced recursively in `FieldGroupPersistence`.

### `builder_field_options`

Options for `select`, `radio`, `multiselect`, `checkbox_list`, `button_group`.

### `builder_field_values`

One row per value:

| Column | Field types |
|--------|-------------|
| `entity` | e.g. `item` |
| `record_id` | Record ID |
| `field_name` | Field key |
| `value_string` | text, email, url, select, password, oembed, … |
| `value_text` | textarea, rich_text |
| `value_decimal` | number, range |
| `value_date` | date |
| `value_datetime` | datetime |
| `value_boolean` | toggle |
| `value_json` | multiselect, checkbox_list, link, image, gallery, file, group, repeater, flexible_content |
| `locale` | Locale variant for this value (e.g. `en_US`, `de_CH`) |

Unique: `(entity, record_id, field_name, locale)`.

### Definition translations (Astrotomic)

Field group names, field labels, option labels, and translatable field config (`helperText`, `placeholder`, …) use [astrotomic/laravel-translatable](https://docs.astrotomic.info/laravel-translatable) — the same stack as `moox/media` and Moox draft entities (via `moox/localization`).

| Table | Model | Translated attributes |
|-------|-------|----------------------|
| `builder_field_group_translations` | `FieldGroupTranslation` | `name` |
| `builder_field_translations` | `FieldTranslation` | `label`, `config` (JSON subset) |
| `builder_field_option_translations` | `FieldOptionTranslation` | `label` |

`FieldGroup`, `Field`, and `FieldOption` implement `TranslatableContract` through `HasBuilderTranslatableAttributes`. Main-table columns (`name`, `label`, …) stay populated for the default locale as fallback columns.

**Not translated:** field keys (`name`), option values (`value`), structural `config`, location rules, validation rules.

### Location Rules (internal)

In admin you choose **"Show on"** (multi-select). Internally this becomes:

```json
[
  [{ "param": "entity", "operator": "==", "value": "item" }],
  [{ "param": "entity", "operator": "==", "value": "record" }]
]
```

Each inner list = AND group, multiple groups = OR. The matcher currently only supports `param: entity` with `==` / `!=`. Without assignment ("Show on" empty) the group appears in no form.

---

## Runtime Flow

### Open form (create/edit)

```
1. Resource::form() includes ...static::customFieldComponents()

2. HasCustomFields → DefinitionRegistry::fieldGroupsFor(LocationContext)
   → loads cached groups
   → LocationMatcher filters by entity

3. SchemaCompiler::compile()
   → one Filament section per group
   → layout fields: tabs, group, repeater, flexible content (Builder)
   → afterStateHydrated loads values via CustomFieldsManager (one query per record, cached)
   → Filament Builder: hydrateItems() for UUID-based block keys
```

### Save

```
1. Filament saves the model (title, description, …)

2. RecordSaved event fires

3. PersistCustomFields::handle($record, $data, $page)
   → checks: does the resource use HasCustomFields?

4. CustomFieldsManager::saveFromFormData()
   → extracts known field keys from $data
   → FieldValueValidator + OptionValueRules
   → updateOrCreate in builder_field_values
```

**Important:** No page hooks (`afterCreate`, `mutateFormDataBeforeSave`) needed.

### Cache

`DefinitionRegistry` caches under `builder.definitions` as **PHP arrays**.

Invalidation is automatic via `InvalidateDefinitionCacheObserver` when groups/fields/options change.

Manual: `php artisan cache:forget builder.definitions`

---

## Installation

### Via Moox Installer

```bash
composer require moox/builder
php artisan moox:install
```

Select migrations, config, seeder, and `BuilderPlugin`.

### Manual

```bash
composer require moox/builder
php artisan vendor:publish --tag=builder-config
php artisan vendor:publish --tag=builder-migrations
php artisan migrate
php artisan db:seed --class="Moox\Builder\Database\Seeders\BuilderSeeder"
```

Register the plugin in your panel:

```php
use Moox\Builder\Plugins\BuilderPlugin;

$panel->plugins([
    BuilderPlugin::make(),
]);
```

---

## Connect a Resource

### Step 1: Trait on the Filament resource

```php
use Moox\Builder\Concerns\HasCustomFields;

class ItemResource extends Resource
{
    use HasCustomFields;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // your own fields …
            ...static::customFieldComponents(),
        ]);
    }
}
```

The **entity key** is derived automatically from the model basename (`Item` → `item`). Override with `customFieldsEntity()`:

```php
protected static function customFieldsEntity(): ?string
{
    return 'item';
}
```

### Step 2: Entity discovery

`EntityRegistry` finds resources **automatically** across all registered Filament panels — every resource with `HasCustomFields` appears in the "Show on" multi-select. No manual config registration needed.

### Step 3: Field group in admin

**Fields → Field Groups → Create**

- **Show on:** e.g. `Items`, `Records`
- Define fields
- Keep active

**Not required:**

- Trait or column on the Eloquent model
- Custom listeners or page hooks
- Migration for JSON on the model
- Manual entity config

---

## Field Groups in Admin

Navigation: **Fields → Field Groups**

| Section | Content |
|---------|---------|
| **General** | Name, technical key, active, sort order |
| **Assignment** | "Show on" multi-select (auto-discovered resources) |
| **Fields** | Repeater: label, type, field key, required |
| **Settings** | Capability fields (only when the type has them) |
| **Options** | For select/radio/multiselect/checkbox list |
| **Subfields** | For group and repeater |
| **Layouts** | For flexible content (layout key + subfields) |

Repeater rows are collapsed by default and show type, key, and required flag in the label.

Use the language selector (`?lang=`) on create/edit to translate group names, field labels, option labels, and translatable config. See [Translations](#translations).

Package UI strings: `resources/lang/de/builder.php`, `en/builder.php`.

---

## Field Types & Capabilities

### Built-in field types (28 with `moox/media`, otherwise 25)

| Category | Keys |
|----------|------|
| **Text** | `text`, `textarea`, `email`, `url`, `password`, `rich_text` |
| **Number** | `number`, `range` |
| **Choice** | `select`, `multiselect`, `checkbox_list`, `radio`, `button_group`, `toggle` |
| **Date** | `date`, `datetime`, `time` |
| **Other** | `color`, `link`, `message`, `oembed` |
| **Media** *(requires `moox/media`)* | `image`, `gallery`, `file` |
| **Layout** | `tab`, `group`, `repeater`, `flexible_content` |

Internal only (DB, not selectable): `flexible_layout` — defines a layout inside flexible content.

### Media fields (`moox/media` optional)

Types `image`, `gallery`, and `file` are only registered when `moox/media` is installed (`MediaIntegration::isAvailable()`). There is **no** hard Composer dependency — without the media package these types are missing in admin.

| Type | UI | Storage in `value_json` | Library filter |
|------|----|---------------------------|----------------|
| `image` | Single media picker | One snapshot `{id, file_name, title, alt, …}` | images only |
| `gallery` | Multi picker | Indexed snapshots `{"1": {…}, "2": {…}}` | images only |
| `file` | Single media picker | One snapshot (like image) | everything except images |

**Architecture:**

- UI uses the Moox media library (`BuilderMediaPicker` + isolated modal per field)
- Values go to `builder_field_values`, not model columns
- `media_usables` tracks usage; snapshots sync on translation updates
- Validation: media exists, scope matches the record, MIME type matches the field type

API output uses `presentValue()` / `MediaItemResource` (URLs, thumbnails — no `internal_note`). See [API Serialization](#api-serialization).

### Layout fields

| Type | Filament component | Storage |
|------|-------------------|---------|
| `tab` | `Tabs` / `Tab` (marker, no value) | — |
| `group` | `Repeater` (min/max 1) | `value_json` (object) |
| `repeater` | `Repeater` | `value_json` (array) |
| `flexible_content` | `Builder` with layout blocks | `value_json` (array with `type` + `data`) |

Flexible content works like ACF **Flexible Content**: each row is a selectable layout with its own subfields.

### Capabilities (configurable per type)

| Capability | Effect |
|------------|--------|
| `MaxLength` | Max character length |
| `Placeholder` | Placeholder text |
| `PrefixSuffix` | Prefix/suffix |
| `DefaultValue` | Default value |
| `HelperText` | Help text below the field |
| `MinValue` / `MaxValue` / `Step` | Number fields |
| `Rows` | Textarea rows |
| `DisplayFormat` | Date format |
| `MessageBody` | Info text (message) |
| `RepeaterItems` | Min/max entries (repeater, flexible content) |
| `GalleryFiles` | Min/max files (gallery) |

Each field type implements `FieldType`: `key()`, `formComponent()`, `capabilities()`, optionally `castValue()`, `hasSubFields()`, `hasLayouts()`.

### Validation

- **Required fields** and **capabilities** are applied as Filament rules on the component.
- **Nested values** (repeater, group, flexible content) are also validated by `FieldValueValidator` — including empty repeater rows and unknown layouts.
- **Media fields:** existence, scope, MIME type (image vs file), and min/max files for gallery.

---

## Translations

Builder separates **definitions** (what fields are called) from **values** (what users enter).

| Layer | Mechanism | Locale source |
|-------|-----------|---------------|
| **Definitions** | Astrotomic translation tables | `?lang=` on field group admin, `BuilderLocaleResolver` |
| **Values** | `locale` column on `builder_field_values` | `?lang=` / `request('lang')` on consumer resources |

### Locale resolution

`BuilderLocaleResolver` resolves in order:

1. Explicit locale argument
2. `request('lang')` (query or input)
3. Default locale from `moox/localization` (`localizations.is_default`)
4. Admin UI default from `adminDefaultLocale()` (`is_default` + `is_active_admin`, then first active admin locale)
5. `config('builder.default_locale')` → `config('app.locale')` → `en_US`

Fallback chain for missing translations: **active locale → default locale → main-table columns**.

### Admin: field groups

`EditFieldGroup` / `CreateFieldGroup` / `ListFieldGroups` use `InteractsWithFieldGroupLocale` (same patterns as Moox draft list/edit pages):

- Language selector in the header (`localization::lang-selector` when available)
- `?lang=de_CH` saves labels/names into the matching translation row via `translateOrNew()` + `saveTranslations()`
- `hydrate()` keeps `request('lang')` in sync on Livewire re-renders (search/filter)
- Invalid or admin-hidden locales redirect to the default admin locale (`is_active_admin`)
- Default locale also updates main-table fallback columns
- List index shows localized group names for the active `?lang=`

### Runtime: consumer resources

No page changes are required. When a resource uses `HasCustomFields`, the builder package automatically:

- Registers panel middleware that keeps `?lang=` in session (also for Livewire subrequests)
- Shows the language selector on list, create, edit, and view pages
- Loads and saves `builder_field_values` for the active locale via `BuilderLocaleResolver`

Definition labels in forms come from `DefinitionRegistry` + `DefinitionTranslator` (cached, localized at read time).

### Code patterns (same as Media/Draft)

```php
$group->translateOrNew('de_CH')->name = 'Grundlagen';
$group->saveTranslations();

$field->translateOrNew('de_CH')->label = 'Farbe';
$field->saveTranslations();
```

Astrotomic is provided transitively via `moox/core` → `moox/localization` — no extra Composer dependencies needed in this package.

---

## Configuration

`config/builder.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `navigation_group` | `Fields` | Filament navigation group for field groups |
| `default_locale` | `en_US` | Fallback when `moox/localization` has no default |

Env: `BUILDER_NAVIGATION_GROUP`, `BUILDER_DEFAULT_LOCALE`.

---

## Extension

### Register a custom field type

```php
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\Registry\FieldTypeRegistry;

$this->app->afterResolving(FieldTypeRegistry::class, function (FieldTypeRegistry $registry) {
    $registry->register(new MyCustomFieldType);
});
```

Translation under `builder::builder.field_types.{key}` in `resources/lang`.

### Get validation rules

```php
ItemResource::customFieldRules();
// ['field-name' => ['required', 'max:255'], 'repeater.*.subfield' => ['required'], ...]
```

### Load values for Filament forms

```php
use Moox\Builder\Services\CustomFieldsManager;

$values = app(CustomFieldsManager::class)->loadFormData(
    ItemResource::class,
    $item,
);
```

This is for the Filament form context, not for API output.

---

## Model API

Add `InteractsWithCustomFields` to the consumer model (e.g. `Item`). Entity key resolution: `getResourceName()` → `customFieldsEntity()` → Filament resource → model basename.

```php
use Moox\Builder\Concerns\InteractsWithCustomFields;

// Read
$item->color;                              // like a native attribute (valid PHP field names)
$item->customFields();                     // all custom fields including defaults
$item->customFields(fresh: true);           // reload from DB, ignore cache
$item->customField('vehicle-type');        // single field
$item->hasCustomField('color');            // value present (including default)?
$item->hasCustomFieldDefinition('color');   // field definition exists?
$item->toArray();                          // DB columns + raw custom fields (internal)

// Write 
$item->color = 'Blue';                     // or setCustomField()
$item->setCustomField('color', 'Blue');
$item->setCustomFields(['accident_free' => true]);
$item->clearCustomField('color');

// Queries & collections
Item::query()->where('color', 'Blue')->get();        // normal where on custom fields
Item::query()->withCustomFields()->get();            // eager load (no N+1)
Item::eagerLoadCustomFields($models);                // batch for existing collection

// Meta
Item::customFieldNames();                            // all defined field names
Item::resolveCustomFieldsEntity();                   // entity key (e.g. item)
Item::flushCustomFieldDefinitionCache();             // clear definition cache
$item->flushCustomFieldsCache();                     // clear value cache on the model

// Optional: custom entity key (same hook as on the Filament resource)
protected static function customFieldsEntity(): ?string
{
    return 'my-entity';
}
```

**Notes:**

- DB columns take precedence over custom fields with the same name (`$item->title` → column, not builder field).
- Password fields are hashed on save (`Hash::make`) and never returned in plain text.
- `dump($item)` / Tinker shows custom fields in `__debugInfo()` (passwords masked).

---

## API Serialization

Builder does **not** ship HTTP routes. It provides helpers for your own `JsonResource` classes or controllers.

### Requirements

1. The Eloquent model must use `InteractsWithCustomFields`.
2. Without that trait, `mergeCustomFields()` returns your payload unchanged (no error).

### JsonResource (recommended)

```php
use Illuminate\Http\Resources\Json\JsonResource;
use Moox\Builder\Http\Resources\Concerns\MergesCustomFields;

class ItemApiResource extends JsonResource
{
    use MergesCustomFields;

    public function toArray($request): array
    {
        return $this->mergeCustomFields([
            'id' => $this->id,
            'title' => $this->title,
        ]);
    }
}
```

Use a distinct class name (e.g. `ItemApiResource`) — do not confuse it with the Filament `ItemResource`.

### Lists (avoid N+1)

```php
$items = Item::query()->withCustomFields()->get();

return ItemApiResource::collection($items);
```

### Internal vs API output

| Method | Use case | Example: `date` | Example: `password` | Example: `image` |
|--------|----------|-----------------|---------------------|-------------------|
| `$item->customFields()` | Internal / PHP | `Carbon` instance | hashed string | snapshot array |
| `$item->toArray()` | Internal arrays | `Carbon` instance | `null` | snapshot array |
| `mergeCustomFields()` | API / JSON | `"2026-06-16"` | `{"has_value": true}` | `MediaItemResource` shape |

### Output shapes (API)

| Field type | API output |
|------------|------------|
| `date` | `"2026-06-16"` |
| `datetime` | ISO 8601 string |
| `time` | `"14:30"` |
| `password` | `{"has_value": true}` |
| `image` / `file` | `MediaItemResource` (`url`, `thumbnail_url`, `preview_url`, …) |
| `gallery` | `{"1": {...}, "2": {...}}` (indexed) |
| `group` | `{"subfield": ...}` (nested, presented) |
| `repeater` | `[{...}, {...}]` |
| `flexible_content` | `[{"type": "hero", "data": {...}}]` |

### Without JsonResource

```php
use Moox\Builder\Services\BuilderValuesResolver;
use Moox\Builder\Services\CustomFieldsManager;

$manager = app(CustomFieldsManager::class);
$entity = $item::resolveCustomFieldsEntity();

$presented = app(BuilderValuesResolver::class)->present(
    $manager->fieldsForEntity($entity),
    $item->customFields(),
);
```

---

## Package Structure

```
packages/builder/
├── config/builder.php
├── database/
│   ├── migrations/          # 7 tables + translation tables
│   └── seeders/BuilderSeeder.php
├── resources/lang/{de,en}/builder.php
└── src/
    ├── BuilderServiceProvider.php
    ├── Concerns/
    │   ├── HasCustomFields.php              # Filament resource
    │   └── InteractsWithCustomFields.php    # Consumer model
    ├── Filament/Resources/Pages/Concerns/
    │   └── InteractsWithBuilderLocale.php
    ├── Compiler/
    │   ├── LocationMatcher.php
    │   └── SchemaCompiler.php
    ├── Data/
    │   ├── FieldDefinition.php
    │   ├── FieldGroupDefinition.php
    │   └── LocationContext.php
    ├── FieldTypes/
    │   ├── FieldType.php
    │   ├── Capabilities/
    │   └── Types/                         # 29 field types
    ├── Forms/Components/BuilderMediaPicker.php
    ├── Http/
    │   ├── Middleware/ResolveBuilderAdminLocale.php
    │   ├── Livewire/BuilderMediaPickerModal.php
    │   └── Resources/Concerns/MergesCustomFields.php
    ├── Listeners/PersistCustomFields.php
    ├── Models/
    │   ├── Concerns/HasBuilderTranslatableAttributes.php
    │   ├── FieldGroup.php, Field.php, FieldOption.php, FieldValue.php
    │   └── *Translation.php                 # Astrotomic translation models
    ├── Observers/
    │   ├── InvalidateDefinitionCacheObserver.php
    │   └── PurgeFieldValuesObserver.php
    ├── Plugins/BuilderPlugin.php
    ├── Registry/
    │   ├── DefinitionRegistry.php
    │   ├── EntityRegistry.php
    │   └── FieldTypeRegistry.php
    ├── Resources/FieldGroupResource.php   # Admin UI
    ├── Services/
    │   ├── CustomFieldsManager.php
    │   ├── BuilderValuesResolver.php
    │   ├── BuilderMediaUsageSync.php
    │   ├── BuilderFieldValueMediaMetadataSync.php
    │   ├── FieldGroupPersistence.php
    │   ├── FieldGroupValidator.php
    │   ├── FieldValuePurger.php
    │   └── FieldValueValidator.php
    └── Support/
        ├── BuilderLocaleResolver.php
        ├── CustomFieldsFilamentHooks.php
        ├── DefinitionTranslator.php
        ├── EntityModelDeletionRegistrar.php
        ├── MediaFieldValueSupport.php
        ├── MediaIntegration.php
        ├── OptionValueRules.php
        └── TypedValueColumns.php
```

---

## Testing

### Package tests

```bash
cd packages/builder && composer test
```

### Manual in the panel

1. **Fields → Field Groups** — demo group "Vehicle data" (after seeder)
2. **Items → Edit** — custom field sections including tabs, group, repeater, flexible content, optional image/gallery/file
3. Save, then check the DB:

```sql
SELECT entity, record_id, field_name, locale, value_string, value_decimal, value_json
FROM builder_field_values
WHERE entity = 'item';
```

4. Re-open the item — values must be loaded.

### Seeder

```bash
php artisan db:seed --class="Moox\Builder\Database\Seeders\BuilderSeeder" --force
```

### Checklist

| Check | Expected |
|-------|----------|
| 4 builder tables exist | after `migrate` |
| Resource uses `HasCustomFields` | appears under "Show on" |
| `BuilderPlugin` in panel | "Fields" nav visible |
| Field group with "Show on: Items" | section on item form |
| Values in `builder_field_values` | typed columns filled |
| Flexible content sortable | no errors after save/load |
| Image/gallery/file (with `moox/media`) | library filtered, save/load works, `media_usables` updated |

---

## Limits & Roadmap

**Implemented:**

- Nested fields via `parent_field_id` (group, repeater, flexible content)
- Layout fields: tab, group, repeater, flexible content
- Entity discovery via `HasCustomFields` in Filament panels
- Nested validation (`FieldValueValidator`)
- `InteractsWithCustomFields` on consumer models (`customFields()`, attribute access, queries, eager load)
- `MergesCustomFields` for API resources
- `FieldType::presentValue()` for API serialization (ISO dates, password masking, nested fields, media via `MediaItemResource`)
- Repeater min/max (`RepeaterItems` capability)
- Media fields: `image`, `gallery`, `file` (optional with `moox/media`)
- `BuilderMediaPicker` with isolated modal per field and MIME filter in the library
- `media_usables` sync and metadata snapshot updates for media fields

**Not implemented yet:**

- Relational fields (post object, relationship, user, taxonomy)
- Clone field type (ACF)
- Location params beyond `entity`
- `placement` control (sidebar, …)
- Conditional logic in forms

**Intentionally out of scope:**

- WordPress/postmeta driver
- JSON column on the model
- Accordion as its own field type (tabs + sections are enough)

---

## License

MIT
