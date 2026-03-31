# Moox Scope

This guide is **minimal and practical**.

Rule of thumb:
**A model is scopable if (and only if) it has a nullable `scope` column (`string|null`).**

---

## 1) Scope string format

Records store a scope as a string:

`origin:source:context:boundary`

Example:

- `media:draft:jobapplications:private`

Meaning:

- **origin**: which record type stores the scope (e.g. `media`, `category`, `tag`)
- **source**: which “parent context type” (e.g. `draft`, later `career`)
- **context**: concrete bucket inside that source (e.g. `jobapplications`)
- **boundary**: boundary bucket (`private`, `public`, `group`, `user`, `user_type`)

### Global / unassigned

Global is **not** a scope key. Global means:

- `scope IS NULL` (or `scope = ''`)

Global resource views show **only** global records by default.

---

## 2) Runtime truth (DB) vs registry (config)

### DB (`scopes` table) = runtime truth

The DB controls what is active/visible at runtime:

- `is_active` controls:
  - scoped child navigation visibility (hidden when inactive)
  - scoped query guards (fail-closed: only active scopes/contexts return records)
- `label` is UI naming.

### Config = registry / whitelist / mapping

Config does **not** decide runtime visibility. Config defines what the codebase supports.

#### `packages/core/config/core.php`

This is the registry for translating scope keys ↔ model classes:

```php
'scopes' => [
    'origins' => [
        'media' => \Moox\Media\Models\Media::class,
        'category' => \Moox\Category\Models\Category::class,
        // ...
    ],
    'sources' => [
        'draft' => \Moox\Draft\Models\Draft::class,
        // ...
    ],
],
```

Why we need this mapping:

- **Reverse lookup (write validation)**: record model → expected origin key
- **Whitelist**: only known keys are considered supported by the project
- **Bootstrapping**: UI/dev tools need keys even when DB is empty

---

## 3) Make a model scopable (copy/paste checklist)

### 3.1 Add the `scope` column

Migration snippet:

```php
$table->string('scope')->nullable()->index();
```

Convention:

- `NULL`/`''` = global/unassigned

### 3.2 Use Moox base resources (query scoping)

Resources that extend `Moox\Core\Entities\BaseResource` automatically call:

- `ScopedResourceContext::applyScope($query, static::class)`

Effect:

- **scoped list view** → filtered by `exact` or `context`
- **global list view** → only `scope IS NULL OR scope=''`

### 3.3 Ensure “Create” applies defaults in scoped contexts

Moox base create pages call:

- `ScopedResourceContext::applyDefaults($record, static::getResource())`

So creating a record inside a scoped child resource automatically writes the correct 4-part scope string.

### 3.4 (Optional) enable bulk “Assign scope”

If a resource uses:

- `Moox\Core\Support\Resources\Concerns\HasScopedChildResource`

Then it can provide a bulk action that moves records between scopes by writing the `scope` column.

---

## 4) Global resource registration (example: Categories)

If you want a **global** admin resource (unscoped), register it explicitly via `ResourceNavigationRegistrar`.

### `packages/category/src/Moox/Plugins/CategoryPlugin.php`

```php
use Moox\Core\Support\Resources\ResourceNavigationRegistrar;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;

public function register(Panel $panel): void
{
    ResourceNavigationRegistrar::register($panel, [
        CategoryResource::class,
    ]);
}
```

What this does:

- `$panel->resources([...])` → registers pages/routes for the resource
- `$panel->navigationItems([...])` → forces a global navigation item using the resource’s own navigation methods

This is useful when scoped child navigation is also present, so global resources don’t “disappear” due to navigation composition.

---

## 5) Scoped child resources under a parent (example: Draft)

You define child resources in the parent feature config, then register them in the parent plugin using `ChildResourceRegistrar`.

### 5.1 Define scoped children in config

Location:

- `packages/draft/config/draft.php`

Example (real, from our repo):

```php
'resources' => [
    'draft' => [
        'scopes' => [
            'media' => [
                'enabled' => true,
                'resource' => \Moox\Media\Resources\MediaResource::class,
                'origin' => 'media',
                'boundary' => 'private',
                'label' => 'Media Private',
            ],
            'media_public' => [
                'enabled' => true,
                'resource' => \Moox\Media\Resources\MediaResource::class,
                'origin' => 'media',
                'boundary' => 'public',
                'label' => 'Media Public',
            ],
            'tag' => [
                'enabled' => true,
                'resource' => \Moox\Tag\Resources\TagResource::class,
            ],
            'category' => [
                'enabled' => false,
                'resource' => \Moox\Category\Moox\Entities\Categories\Category\CategoryResource::class,
                'origin' => 'category',
                'boundary' => 'private',
                'label' => 'Category Private',
            ],
        ],
    ],
],
```

Notes:

- `resource` is the Filament resource class that will be registered for this scoped child.
- `enabled` is informational only (runtime activation is controlled by DB `scopes.is_active`).
- `origin`/`boundary`/`label` are optional overrides. If omitted, the system derives defaults from keys and the parent context.

### 5.2 Register the parent definition in the plugin

Location:

- `packages/draft/src/Moox/Plugins/DraftPlugin.php`

The key call:

```php
ChildResourceRegistrar::registerFromParentDefinition(
    $panel,
    DraftResource::class,
    'draft',
    config('draft.resources.draft', []),
);
```

What happens:

1. The parent resource is registered in the panel.
2. Each child resource is registered as a **resource configuration** (same PHP class, different configuration key).
3. A navigation item is added **only if** the corresponding DB scope exists and `is_active=true` (fail-closed).

### 5.3 Sync config → DB (`scopes` table)

```bash
php artisan scopes:sync
```

Then activate/deactivate via the Scopes UI.

---

## 6) What “is_active” affects

- **Navigation**: scoped child nav item appears only when its scope is present and active.
- **Queries**: `ScopeQuery` applies DB guards so inactive scopes do not return data.
- **Bulk Assign options**: only active scopes are offered for assignment.

---

## 7) exact vs context (scope_match)

- `exact` → matches the full `origin:source:context:boundary`
- `context` → matches `origin:source:context:%` (boundary ignored)

Default behavior (when not explicitly set) is derived from the DB:

- if there is **more than one active boundary** for the same `origin/source/context` → default is `exact`
- else → default is `context`

