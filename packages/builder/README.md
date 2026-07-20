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
6. [Quick Start](#quick-start)
7. [Connect a Resource](#connect-a-resource)
8. [Field Groups in Admin](#field-groups-in-admin)
9. [Table columns](#table-columns)
10. [Table filters](#table-filters)
11. [Field Types & Capabilities](#field-types--capabilities)
12. [Translations](#translations)
13. [Configuration](#configuration)
14. [Extension](#extension)
15. [Model API](#model-api)
16. [API Serialization](#api-serialization)
17. [Package Structure](#package-structure)
18. [Testing](#testing)
19. [Limits & Roadmap](#limits--roadmap)

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
| `placement` | Form slot: `main` (default) or `sidebar`. Consumers render each via `customFieldComponents($placement)` |
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
| `value_json` | multiselect, checkbox_list, link, relation, image, gallery, file, group, repeater, flexible_content |
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

### Location Rules

In admin you choose **"Show on"** (multi-select) and optional **"Additional conditions"**.

The admin UI stores the same `location_rules` structure as before, but the inputs are safer and more guided now:

- **Taxonomy** conditions use selects for taxonomy keys and taxonomy terms. Labels are shown, internal IDs are stored.
- **Record type** conditions use known type options from the target resource (`getTypeSelect()`) and existing records. If no records exist yet, resource-defined type options still appear.
- **User role** remains visible in the UI, but becomes disabled with an explanation when roles are not configured, no roles exist yet, or the configured auth user model does not use `HasRoles`.
- Changing the selected condition parameter resets dependent fields (`taxonomy`, operator default, value) to avoid stale form state.

Internally this becomes OR groups with AND rules:

```json
[
  [
    { "param": "entity", "operator": "==", "value": "draft" },
    { "param": "record_type", "operator": "==", "value": "page" }
  ]
]
```

| Param | Example | Context source |
|-------|---------|----------------|
| `entity` | `item`, `draft` | Resource / model entity key |
| `record_type` | `page`, `article` | `$record->type` or `customFieldsLocationParams()` |
| `record_status` | `draft`, `published` | Draft entities: current locale `translation_status`; otherwise main-table `status` when present |
| `user_role` | `admin`, `editor` | Authenticated user roles (`in` / `not in` supported when roles are available) |
| `taxonomy:{key}` | term ID `12` or `12,34` | Taxonomy IDs on the record (`HasModelTaxonomy`) |

- Outer array = **OR** groups; inner array = **AND** rules.
- Empty rules = matches nothing (fail-closed).
- On create forms (no record yet), record/taxonomy rules are ignored; entity and user-role rules still apply.
- Per-record matching also runs on compiled form sections via `SchemaCompiler`.
- `LocationConstraintOptions` resolves taxonomy term labels with locale fallback: active/admin locale → default locale → English → first available translation.
- `FieldGroupValidator` re-validates condition params, operators, and submitted values server-side, so manipulated requests cannot persist invalid location constraints.

Override or extend auto-detected params on the resource/model:

```php
protected static function customFieldsLocationParams(?Model $record): array
{
    return ['record_type' => $record?->type];
}
```

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
   → conditional logic: visible() closures on fields with rules
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
   → only admin-visible fields accept submitted values (crafting hidden fields is ignored)
   → FieldValueValidator (includes option/relation/media rules)
   → fields hidden by conditional logic skip validation and are cleared on save
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

## Quick Start

Get custom fields on a Filament resource in a few minutes. No JSON column on the model, no per-field migrations.

### 1. Install (once per project)

Follow [Installation](#installation) above: package, migrations, `BuilderPlugin` in the panel. After that, **Fields → Field Groups** appears in the admin.

### 2. Wire the Filament resource (required)

Add the trait and spread compiled sections into your form schema:

```php
use Moox\Core\Traits\HasCustomFields; // Moox monorepo alias → moox/builder
// Or: use Moox\Builder\Concerns\HasCustomFields;

class ItemResource extends BaseItemResource
{
    use HasCustomFields;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // your own fields …
            ...static::customFieldComponents(),           // main area (default)
            ...static::customFieldComponents('sidebar'),  // optional sidebar slot
        ]);
    }
}
```

Loading and saving run automatically via Filament `RecordSaved` — no `afterCreate`, no custom listeners.

The **entity key** defaults to the model basename (`Item` → `item`). Override only when needed — see [Connect a Resource](#connect-a-resource).

### 3. Create a field group in admin (required)

**Fields → Field Groups → Create**

| Setting | Action |
|---------|--------|
| **Show on** | Select your resource (e.g. Items) — auto-discovered when the resource uses `HasCustomFields` |
| **Fields** | Add label, type, and technical key (`farbe`, `preis`, …) |
| **Active** | On |

Open the resource create/edit form — custom field sections should appear.

### 4. Optional next steps

| Goal | What to add |
|------|-------------|
| Read/write in PHP, queries, Tinker | `InteractsWithCustomFields` on the model — [Model API](#model-api) |
| List table columns | `...static::customFieldColumns()` in `table()` (enable **Show in table** on the field) |
| List table filters | `...static::customFieldFilters()` in `table()` (enable **Show in table filter** on the field) |
| REST / JSON API | `MergesCustomFields` on your `JsonResource` — [API Serialization](#api-serialization) |
| Per-locale values | Translatable model, or `customFieldsAreTranslatable(): true` — [Translations](#translations) |
| Show only on some records | Location rules in the field group — [Location Rules](#location-rules) |

### Live examples in this monorepo

Branch: **`feature/custom-fields`** (includes list table filters).

```bash
git fetch origin
git checkout feature/custom-fields
composer install
php artisan migrate   # if builder tables are not present yet
```

| Piece | Path |
|-------|------|
| Resource (filters wired) | `packages/item/src/Resources/ItemResource.php` |
| Model | `packages/item/src/Models/Item.php` |
| Same pattern | `packages/record`, `packages/draft` |
| Tests | `packages/builder/tests/Support/TestItemResource.php` |

Built-in list resources already call `...static::customFieldFilters()` with `deferFilters(false)` and `persistFiltersInSession()` — see [Table filters](#table-filters).

### Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Resource missing from **Show on** | Resource has no `HasCustomFields` | Add trait; ensure resource is in a registered Filament panel |
| No sections on the form | Inactive group, wrong entity, or location rules | Check group is active, **Show on**, and [location rules](#location-rules) |
| Sidebar empty | Group placement is `sidebar` but form has no sidebar call | Add `...static::customFieldComponents('sidebar')` in the sidebar column |
| `$item->feld` does not work | Model missing trait | Add `InteractsWithCustomFields` to the model |
| No filter on list | Toggle not saved, wrong list resource, or unsupported field config | Enable **Show in table filter** under **List filter**, save the group, open Item/Record/Draft list, click the funnel icon — see [Table filters](#table-filters) |
| Filter missing for relation | Multiple relation or no related entity | Use single relation and pick a related entity first |
| Filter toggle disabled in admin | Choice field has no options yet | Add at least one option, then enable the list filter |
| Filter chip visible but no results | Values stored under another locale | Switch `?lang=` on the list page to match stored `builder_field_values.locale` |

Details and overrides: [Connect a Resource](#connect-a-resource). List views: [Table columns](#table-columns), [Table filters](#table-filters). Manual verification: [Testing → Checklist](#checklist).

---

## Connect a Resource

### Step 1: Trait on the Filament resource

```php
use Moox\Core\Traits\HasCustomFields; // Moox monorepo — or Moox\Builder\Concerns\HasCustomFields

class ItemResource extends Resource
{
    use HasCustomFields;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // your own fields …
            ...static::customFieldComponents(),           // groups placed in the main area
            ...static::customFieldComponents('sidebar'),  // groups placed in the sidebar
        ]);
    }
}
```

A group's **placement** (`main` by default, or `sidebar`) decides which slot it renders in. Sidebar groups only appear where the resource form actually has a sidebar column and calls `customFieldComponents('sidebar')` there.

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

### Step 4: List views (optional)

| Goal | Resource `table()` | Field group admin |
|------|-------------------|-------------------|
| Columns | `...static::customFieldColumns()` | **Table column** → **Show in table** |
| Filters | `...static::customFieldFilters()` + `deferFilters(false)` | **List filter** → **Show in table filter** |

See [Table columns](#table-columns) and [Table filters](#table-filters). Item, Record, and Draft in this monorepo already include both.

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
| **Assignment** | "Show on" multi-select (auto-discovered resources) + optional additional conditions (`record_type`, `user_role`, taxonomy) |
| **Fields** | Repeater: label, type, field key, required |
| **Settings** | Capability fields (only when the type has them) |
| **Options** | For select/radio/multiselect/checkbox list |
| **Subfields** | For group and repeater |
| **Layouts** | For flexible content (layout key + subfields) |
| **Visibility** | Per context: admin, frontend, API (`visible_*` toggles) |
| **Conditional logic** | Show/hide rules based on sibling field values (root, group, repeater, flexible layout) |
| **Table column** | Optional list-column settings for scalar, media, and relation fields — see [Table columns](#table-columns) |
| **Table filter** | Optional list-filter chip for select, radio, button_group, toggle, single relation, text-like, and number/range/date/datetime fields — see [Table filters](#table-filters) |

Repeater rows are collapsed by default and show type, key, and required flag in the label.

Use the language selector (`?lang=`) on create/edit to translate group names, field labels, option labels, and translatable config. See [Translations](#translations).

Package UI strings: `resources/lang/de/builder.php`, `en/builder.php`.

---

## Table columns

Opt-in Filament list columns for custom fields. Values are read from `builder_field_values` (with locale resolution) — no extra column on the consumer model.

### Supported field types

| Category | Types | Filament column |
|----------|-------|-----------------|
| **Scalar** | `text`, `textarea`, `email`, `url`, `number`, `range`, `select`, `radio`, `button_group`, `multiselect`, `checkbox_list`, `date`, `datetime`, `time`, `color`, `link`, `oembed` | `TextColumn`, `ColorColumn`, or formatted text |
| **Toggle** | `toggle` | `IconColumn` (boolean) |
| **Media** *(requires `moox/media`)* | `image` | `ImageColumn` |
| **Relation** | `relation` (single or multiple) | `TextColumn` with resolved labels |

**Not supported as columns:** `password`, `rich_text`, layout/compound types (`group`, `repeater`, `flexible_content`, `tab`, `section`, `message`), `gallery`, `file`.

### Admin setup

1. **Fields → Field Groups** → open a field
2. Section **Table column** → enable **Show in table**
3. Optional: sortable, searchable, hidden by default, badge/color/icon presentation (text-based types)
4. Save the field group

### Wire on a resource

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            // your own columns …
            ...static::customFieldColumns(),
        ]);
}
```

`HasCustomFields` also registers `customFieldsModifyTableQuery()` (via Moox `BaseResource` conventions) to eager-load values when columns are present — avoids N+1 on list pages.

### Built-in in this monorepo

**Item**, **Record**, and **Draft** already spread `...static::customFieldColumns()` in their `table()` definitions.

### Notes

- Relation columns resolve labels through `RelationTargetResolver` (same rules as relation fields).
- Sort/search on relation columns may fall back to stored IDs when titles exist only in translation tables.
- Columns respect the active list locale (`?lang=` / session) via `BuilderLocaleResolver`.

---

## Table filters

Opt-in Filament list filter chips for custom fields. Filtering runs as subqueries on `builder_field_values` — the consumer model needs no filter scopes.

One filter chip per filterable field, compiled from the **Show in table filter** toggle:

| Field type | Filter chip |
|------------|-------------|
| `select`, `radio`, `button_group` | `SelectFilter` (options from field definition) |
| `toggle` | `TernaryFilter` (yes / no / any) |
| `relation` (single) | `SelectFilter` (searchable, preloaded) |
| `text`, `textarea`, `email`, `url`, `rich_text` | `Filter` (free-text "contains" search) |
| `number`, `range`, `date`, `datetime` | `Filter` (inclusive "from"/"until" range) |

Choice fields need at least one option; relation fields need `related_entity` set and `multiple` off. Range filters accept either bound alone (open-ended) or both.

**Not filterable at all:** media, multiselect/checkbox list, and compound fields (group, repeater, clone, flexible content) — their values are stored as JSON, not a single comparable value, so no dedicated filter chip is compiled for these.

### Admin setup

1. **Fields → Field Groups** → open a filterable field
2. Section **List filter** → enable **Show in table filter**
3. Save the field group
4. Open the resource list (e.g. Items) → funnel icon in the table toolbar → pick a value

The toggle is disabled in admin when prerequisites are missing (e.g. relation without target entity, choice field without options, multiple relation).

### Wire on a resource

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ...
            ...static::customFieldColumns(),
        ])
        ->filters([
            // your own filters …
            ...static::customFieldFilters(),
        ])
        ->deferFilters(false)          // recommended: filters visible without "Apply"
        ->persistFiltersInSession();   // optional: remember filter state per session
}
```

| Method | Role |
|--------|------|
| `customFieldFilters()` | Compiles `TableFilterCompiler` output for visible field groups |
| `deferFilters(false)` | Shows filter controls immediately (Filament default defers them) |
| `persistFiltersInSession()` | Keeps selected filters across list navigation |

### Built-in in this monorepo

| Resource | Filters wired |
|----------|---------------|
| `packages/item/src/Resources/ItemResource.php` | `customFieldFilters()` + `deferFilters(false)` + `persistFiltersInSession()` |
| `packages/record/src/Resources/RecordResource.php` | same |
| `packages/draft/src/Resources/DraftResource.php` | same |

Other resources with `HasCustomFields` do **not** get filters automatically — add the snippet above to `table()`.

### How it works

```
FieldGroup (show_in_filter = true)
    → DefinitionRegistry
    → TableFilterCompiler
    → SelectFilter / TernaryFilter / Filter (text)
    → CustomFieldTableFilterQuery (subquery on builder_field_values)
```

Only fields marked `settings.show_in_filter` in active, location-matched groups are compiled. Filter (chip) names use the field key (`fuel`, `accident_free`, …).

### Troubleshooting

| Symptom | Fix |
|---------|-----|
| No funnel / no custom filters | Resource missing `...static::customFieldFilters()` in `table()` |
| Filter not in admin toggle list | Field type not filterable, or choice/relation preconditions not met |
| Relation filter empty | Set `related_entity`, disable **Multiple**, save, reload field group editor |
| Filter has no effect | Save field group after enabling toggle; ensure records have values for the active locale |
| Filters hidden behind "Apply" | Add `->deferFilters(false)` on the table |

### Tests

```bash
php artisan test --compact packages/builder/tests/Unit/TableFilterCompilerTest.php
php artisan test --compact packages/builder/tests/Unit/FilterableFieldTypesTest.php
```

---

## Field Types & Capabilities

### Built-in field types (29 with `moox/media`, otherwise 26)

| Category | Keys |
|----------|------|
| **Text** | `text`, `textarea`, `email`, `url`, `password`, `rich_text` |
| **Number** | `number`, `range` |
| **Choice** | `select`, `multiselect`, `checkbox_list`, `radio`, `button_group`, `toggle` |
| **Date** | `date`, `datetime`, `time` |
| **Other** | `color`, `link`, `message`, `oembed` |
| **Relation** | `relation` (link to other Moox Filament entities) |
| **Media** *(requires `moox/media`)* | `image`, `gallery`, `file` |
| **Layout** | `tab`, `section`, `group`, `repeater`, `flexible_content` |

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

### Relation fields

Type `relation` links a custom field to records of another Moox entity (any Filament resource with a queryable model — not limited to resources that use custom fields).

| Setting | Effect |
|---------|--------|
| `config.related_entity` | Target entity key (from `EntityRegistry::relatableResources()`) |
| `config.multiple` | Single select vs multi-select |
| `config.min` / `config.max` | Selection limits when `multiple` is enabled |

**Runtime:** searchable `Select` with preloaded suggestions, scoped queries via the target resource's `getEloquentQuery()` (tenant/soft-delete scopes apply).

**Storage:** pure IDs in `value_json` (single ID or array).

**API:** `presentValue()` resolves `{id, label}` objects via `RelationTargetResolver`.

**Label resolution** (picker, API output, validation labels — all via `RelationTargetResolver`):

1. Target resource `recordTitleAttribute` / `getRecordTitle()` when configured on the Filament resource
2. Otherwise the first filled value from: `display_title` → `display_name` → `title` → `name` → `label`
3. A candidate applies when it is a main-table column, an Eloquent accessor (e.g. `getDisplayTitleAttribute()`), or listed in the model's `translatedAttributes` (Astrotomic / Moox draft entities such as categories)
4. Search uses main-table `title` / `name` / `label` when present; otherwise it searches the `translations` relation for those attributes
5. If nothing resolves, the record ID is shown as a last resort

For new relation targets, set `protected static ?string $recordTitleAttribute = 'title';` (or the correct attribute) on the target resource when the display name is not one of the defaults above — especially for translatable entities.

Relation **table columns** (`show in table`) use the same resolver for display; SQL sort/search on relation columns may still fall back to IDs when the title exists only in translation tables.

Invalid or non-relatable `related_entity` values are stripped when field groups are saved.

### Conditional logic

Fields can be shown or hidden based on other field values in the **same container** — root-level siblings, or siblings inside a **group**, **repeater row**, or **flexible content layout**.

| Setting | Values |
|---------|--------|
| `settings.conditions.enabled` | Toggle rules on/off |
| `settings.conditions.action` | `show` or `hide` |
| `settings.conditions.logic` | `and` or `or` |
| `settings.conditions.rules` | `{field, operator, value}` per rule |

**Operators:** `equals`, `not_equals`, `empty`, `not_empty`, `contains`.

**Save behaviour:** hidden fields are not validated; any submitted value for a hidden field is cleared (not persisted). This matches ACF-style semantics and prevents crafted requests from writing unvalidated data.

**Form behaviour:** `SchemaCompiler` applies Filament `visible()` closures that re-evaluate when trigger fields change (`->live()`).

### Field visibility (contexts)

Each field and field group can be toggled per context:

| Context | Key | Wired at runtime |
|---------|-----|------------------|
| Admin | `visible_admin` | Yes — `HasCustomFields`, `saveFromFormData` |
| API | `visible_api` | Yes — `MergesCustomFields` |
| Frontend | `visible_frontend` | Configurable in admin; **no packaged frontend renderer yet** |

Use `CustomFieldsManager::visibleFieldsForEntity($entity, FieldVisibility::API)` (or `ADMIN`) when building custom consumers.

### Layout fields

| Type | Filament component | Storage |
|------|-------------------|---------|
| `tab` | `Tabs` / `Tab` (marker, no value) | — (children store flat) |
| `section` | `Section` (marker, no value) | — (children store flat) |
| `group` | `Repeater` (min/max 1) | `value_json` (object) |
| `clone` | `Fieldset` (references another field group) | `value_json` (object) |
| `repeater` | `Repeater` | `value_json` (array) |
| `flexible_content` | `Builder` with layout blocks | `value_json` (array with `type` + `data`) |

Flexible content works like ACF **Flexible Content**: each row is a selectable layout with its own subfields.

**Clone** works like ACF **Clone**: pick another active field group by slug and embed its root fields inside a `Fieldset`. Subfields are resolved at runtime (reference semantics) — no duplicate field definitions in the database. Values store as a nested JSON object under the clone field name, same as `group`.

`tab` and `section` are **visual-only** markers: they wrap the following fields but their children still store flat at the top level (unlike `group`, which nests values).

### Field width (layout grid)

Every field renders on a fixed **12-column grid**. Each field's `settings.width` fraction is translated into a Filament `columnSpan`, so the widths themselves define the columns — two `1/2` fields sit side by side, three `1/3` fields form a row. No separate "column count" setting is needed.

| Width | columnSpan | Width | columnSpan |
|-------|-----------|-------|-----------|
| `full` (default) | 12 | `2/3` | 8 |
| `1/2` | 6 | `1/4` | 3 |
| `1/3` | 4 | `3/4` | 9 |

Widths apply inside groups, tabs, sections, and repeaters. Defaulting to `full` keeps existing fields rendering exactly as before. Handled by `Moox\Builder\Support\FieldWidth`.

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
| `RelationSettings` | Target entity, multiple, min/max (relation) |

Each field type implements `FieldType`: `key()`, `formComponent()`, `capabilities()`, optionally `castValue()`, `hasSubFields()`.

### Validation

- **Required fields** and **capabilities** are applied as Filament rules on the component.
- **Nested values** (repeater, group, flexible content) are also validated by `FieldValueValidator` — including empty repeater rows and unknown layouts.
- **Media fields:** existence, scope, MIME type (image vs file), and min/max files for gallery.
- **Relation fields:** target exists, scoped to the resource query, min/max when multiple.
- **Conditional logic:** required rules on hidden fields are skipped; hidden values are not persisted.
- **Rich text:** HTML strings and TipTap JSON documents are sanitized on persist via Filament's `Str::sanitizeHtml()` and `RichContentRenderer::toHtml()`; stored values are always safe HTML in `value_text`.

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
| `relation` (single) | `{"id": 1, "label": "Record title"}` |
| `relation` (multiple) | `[{"id": 1, "label": "..."}, ...]` |

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
    │   ├── SchemaCompiler.php
    │   ├── TableColumnCompiler.php
    │   └── TableFilterCompiler.php
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
        ├── CustomFieldTableFilterQuery.php
        ├── FilterableFieldTypes.php
        ├── ConditionalLogic.php
        ├── CustomFieldsFilamentHooks.php
        ├── CustomFieldsTranslatability.php
        ├── DefinitionTranslator.php
        ├── EntityModelDeletionRegistrar.php
        ├── FieldVisibility.php
        ├── FieldWidth.php
        ├── MediaFieldValueSupport.php
        ├── MediaIntegration.php
        ├── OptionValueRules.php
        ├── RelationTargetResolver.php
        ├── RelationValueRules.php
        ├── RichTextValue.php
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
| Relation field on a group | searchable select, save/load IDs, API shows labels |
| Table column on scalar/toggle/media/relation field | column appears (toggle via column picker if hidden by default) |
| Table filter on select/radio/button_group/toggle/relation/text-like field | filter chip on list pages narrows matching records |
| Table filter on number/range/date/datetime field | from/until range filter narrows matching records; either bound alone also works |
| Conditional logic (show/hide) | field visibility updates live; hidden required fields do not block save |
| `?lang=` on translatable resource | values stored per `locale` column |

---

## Limits & Roadmap

**Implemented:**

- Nested fields via `parent_field_id` (group, repeater, flexible content)
- Layout fields: tab, section, group, clone, repeater, flexible content
- Entity discovery via `HasCustomFields` in Filament panels
- Nested validation (`FieldValueValidator`)
- `InteractsWithCustomFields` on consumer models (`customFields()`, attribute access, queries, eager load)
- `MergesCustomFields` for API resources (respects `visible_api`)
- `FieldType::presentValue()` for API serialization (ISO dates, password masking, nested fields, media, relations)
- Repeater min/max (`RepeaterItems` capability)
- Media fields: `image`, `gallery`, `file` (optional with `moox/media`)
- `BuilderMediaPicker` with isolated modal per field and MIME filter in the library
- `media_usables` sync and metadata snapshot updates for media fields
- **Relation fields** — link to Moox Filament entities, scoped search/validation, API `{id, label}` output
- **Conditional logic** — show/hide on sibling fields (root, group, repeater row, flexible layout); save-side enforcement
- **Clone field** — embed another field group by reference (ACF-style)
- **Per-context visibility** — `visible_admin`, `visible_api`, `visible_frontend` (admin + API wired)
- **Location rules** — entity, record type, record status, user role, taxonomy term IDs (admin constraints + import/export)
- **Table columns** — opt-in list columns for scalar, media, and relation fields (`TableColumnCompiler`)
- **Table filters** — opt-in filter chips for select, radio, button_group, toggle, single relation, text-like, and number/range/date/datetime fields (`TableFilterCompiler`, `CustomFieldTableFilterQuery`)
- **Field width grid** — 12-column layout per field (`FieldWidth`)
- **Sidebar placement** — `main` vs `sidebar` field group slots
- **Translations** — definition + value locales (Astrotomic + `builder_field_values.locale`)
- **Security hardening** — admin-hidden fields not writable via request; relation targets whitelisted and scoped; rich-text HTML sanitization on persist
- **Field group import/export** — JSON definition export/import in admin (all locales, no record values)

**v1 limitations (known):**

- Location rules: no template/parent params yet; record/taxonomy/status rules need a saved record (ignored on create)
- Table filters: media, multiselect/checkbox list, and compound fields (JSON-backed values) have no filter chip yet
- Custom `validation.rules`: supported in schema/DB, no admin UI (programmatic only)
- Relation targets: Filament-registered Moox resources only (not arbitrary Eloquent models)

**Not implemented yet:**

- Centralized filter groups / filter presets (per-field list filters only in v1)
- Location params beyond entity/record type/taxonomy/user role (e.g. template, parent)
- Custom validation rules UI in admin
- Package-level policies on field group management

**Intentionally out of scope:**

- WordPress/postmeta driver
- JSON column on the model
- Accordion as its own field type (tabs + sections are enough)
- Packaged HTTP REST routes (use `MergesCustomFields` / `BuilderValuesResolver` in your API layer)

---

## License

MIT
