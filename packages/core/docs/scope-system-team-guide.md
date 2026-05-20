# Moox Scope

This guide is **team standard**: it describes the *one* supported way we configure and use scopes in Moox.

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
- **source**: which "parent context type" (e.g. `draft`, later `career`)
- **context**: concrete bucket inside that source (e.g. `jobapplications`)
- **boundary**: boundary bucket (`private`, `public`, `group`, `user`, `user_type`)

### Global / unassigned

Global is **not** a scope key. Global means:

- `scope IS NULL` (or `scope = ''`)

Global resource views show **only** global records by default.

---

## 2) Runtime truth (DB) vs config

### DB (`scopes` table) = runtime truth

The DB controls what is active/visible at runtime:

- `is_active` controls:
  - scoped child navigation visibility (hidden when inactive)
  - scoped query guards (fail-closed: only active scopes/contexts return records)
- `label` is UI naming (primarily for navigation / admin display). Do not rely on it for user-facing scope selection labels.

### Config = supported capabilities

Config does **not** decide runtime visibility. Config defines what the codebase supports.

**Navigation:** Scoped child nav items are registered only from **active rows in `scopes`**.  
Without a `scopes` table, no scoped children appear in the panel.

**`scopes.allowed` is not navigation** — it is the capability whitelist for:

- **`moox:scope` / `scopes:sync`** — which default scope rows can be created from config
- **Scopes UI (create scope form)** — which origin/source combinations can be selected
- **Child resource mapping** — which Filament `resource` class belongs to an origin under a parent

| Layer | Navigation | Scopes UI (create form) | Record scope select |
| --- | --- | --- | --- |
| `scopes.allowed` | Whitelist only (no menu entries) | Filters Origin + Source dropdowns | — |
| `scopes.registry` | — | Origin keys ↔ models | Registry lookup |
| DB `scopes` | Active rows → child nav (`is_active`) | Saved scope definitions | Options from active rows |

Example: without `tag.resources.tag.scopes.allowed` containing `media`, origin `media` does not appear when creating a scope (and there is no matching source `tag`).

#### 2.1 Registry (which origin/source keys exist?)

The registry for translating scope keys ↔ model classes is defined **inside each resource config** under:

- `config('<package>.resources.*.scopes.registry')` (inside the resource config)

Example:

- `packages/media/config/media.php`

```php
'resources' => [
    'media' => [
        'scopes' => [
            'registry' => [
                'origins' => [
                    'media' => \Moox\Media\Models\Media::class,
                ],
            ],
        ],
    ],
],
```

And:

- `packages/draft/config/draft.php`

```php
'resources' => [
    'draft' => [
        'scopes' => [
            'registry' => [
                'sources' => [
                    'draft' => \Moox\Draft\Models\Draft::class,
                ],
            ],
        ],
    ],
],
```

At runtime, `Moox\Core\Services\ScopeRegistry` builds the complete mapping by merging
all installed Moox packages listed in `config('core.packages')`.

Why we need this mapping:

- **Reverse lookup (write validation)**: record model → expected origin key
- **Whitelist**: only known keys are considered supported by the project
- **Bootstrapping**: UI/dev tools need keys even when DB is empty

#### 2.2 Allowed scopes (which origin is allowed under which source?)

Each *parent* resource can declare which child origins it supports under:

- `config('<package>.resources.<parent>.scopes.allowed')`

This is the **capability / whitelist** layer:

- It defines which `origin` values are meaningful under a given `source` (parent key).
- It maps an `origin` to the Filament `resource` class that should be registered for that scoped child.

This is intentionally separate from runtime visibility (which is DB-driven via `scopes.is_active`).

Used by `ScopeResource` (`packages/scopes`):

- **Origin** — keys from `registry.origins`, but only if at least one parent’s `scopes.allowed` references that origin (`allowedSourcesForOrigin()`), and the origin model is registered in the panel and uses `HasScopedModel`.
- **Source** — parent resource keys (e.g. `tag`, `draft`) whose `scopes.allowed` includes the selected origin (e.g. origin `media` → source `tag` when `tag.resources.tag.scopes.allowed` lists `media`).

---

## 3) Make a model scopable (copy/paste checklist)

### 3.1 Add the `scope` column

Migration snippet:

```php
$table->string('scope')->nullable()->index();
```

Convention:

- `NULL`/`''` = global/unassigned

### 3.2 Add the `HasScopedModel` trait to the model

```php
use Moox\Core\Models\Concerns\HasScopedModel;

class Media extends Model
{
    use HasScopedModel;
}
```

This adds `scope` to `$fillable` and handles default scope assignment on creation.

### 3.3 Use Moox base resources (query scoping + navigation)

Resources that extend `Moox\Core\Entities\BaseResource` automatically get:

- **Query scoping**: `ScopedResourceContext::applyScope($query, static::class)`
- **Navigation resolution**: `getNavigationLabel()`, `getNavigationGroup()`, `getNavigationParentItem()`, `shouldRegisterNavigation()`, `getNavigationSort()` all check for scoped definition values and fall back to defaults.

Effect:

- **scoped list view** → filtered by `exact` or `context`
- **global list view** → only `scope IS NULL OR scope=''`
- **scoped child navigation** → automatically resolved from DB (no boilerplate methods needed per resource)

If a resource needs a custom navigation group, override `resolveDefaultNavigationGroup()`:

```php
protected static function resolveDefaultNavigationGroup(): ?string
{
    return config('media.navigation_group');
}
```

### 3.4 Ensure "Create" applies defaults in scoped contexts

Moox base create pages call:

- `ScopedResourceContext::applyDefaults($record, static::getResource())`

So creating a record inside a scoped child resource automatically writes the correct 4-part scope string.

### 3.5 (Optional) enable bulk "Assign scope"

If a resource uses:

- `Moox\Core\Support\Resources\Concerns\HasScopedChildResource`

Then it can provide a bulk action that moves records between scopes by writing the `scope` column.

### 3.6 Assign scope for a single record (required)

For single records, scopable resources must:

- **show** the current scope on the View page (read-only)
- **allow changing** the scope on the Edit page via a `Scope` select field

Implementation note:

- this is implemented in the **Filament Resource form schema** (not on the model)
- the resource must use `Moox\Core\Support\Resources\Concerns\HasScopedChildResource`
- then you can include the shared field via `static::getScopeSelectField()` wherever it fits your form layout

- resources must also expose a toggleable `Scope` column in the resource table (hidden by default) via `static::getScopeTableColumn()`

Behavior:

- the current scope is preselected (or `Global` when `scope` is `NULL`/`''`)
- options come from active DB rows in the `scopes` table (fail-closed)
- display labels are derived from the 4-part scope string (not from `scopes.label`)

### 3.7 (Required) Make the scope UI not feel broken

The Scopes UI should only offer valid combinations (see `ScopeResource`):

- **Origin** — from `registry.origins`, filtered to origins that appear in at least one `resources.*.scopes.allowed`, with a registered panel resource and `HasScopedModel` on the model
- **Source** — only parent keys whose `scopes.allowed` includes the selected origin (scanning all `config('core.packages')` resource configs)
- Without a matching `allowed` entry (e.g. `media` under `tag.resources.tag.scopes.allowed`), that origin does not appear and no source can be chosen for it

---

## 4) Resource registration via `ChildResourceRegistrar`

All scopable resources (both origins and sources) register via `ChildResourceRegistrar::registerFromParentDefinition()`. This handles both the parent resource and any scoped child navigation items.

### Example: `packages/category/src/Moox/Plugins/CategoryPlugin.php`

```php
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\Category\Resources\CategoryResource;

public function register(Panel $panel): void
{
    ChildResourceRegistrar::registerFromParentDefinition(
        $panel,
        CategoryResource::class,
        'category',
        config('category.resources.category', []),
    );
}
```

What this does:

1. Registers the parent resource in the panel (routes, pages, navigation).
2. Queries the `scopes` table for active scopes under this source and registers scoped child resources / navigation from those rows only (no config fallback when the table is missing or empty).
3. Navigation visibility is controlled by `scopes.is_active` in the DB (fail-closed).

`scopes.allowed` on the parent is not read here for menu items; it remains required for `moox:scope`, the Scopes create form, and origin→resource mapping.

`BaseResource` handles scoped navigation resolution automatically — no boilerplate overrides needed per resource.

---

## 5) Scoped child resources under a parent (example: Draft)

You define child resources in the parent feature config, then register them in the parent plugin using `ChildResourceRegistrar`.

### 5.1 Define scoped child scopes in config

Location:

- `packages/draft/config/draft.php`

Example (real, from our repo):

```php
'resources' => [
    'draft' => [
        'scopes' => [
            'allowed' => [
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
                    'resource' => \Moox\Category\Resources\CategoryResource::class,
                    'origin' => 'category',
                    'boundary' => 'private',
                    'label' => 'Category Private',
                ],
            ],
            'registry' => [
                'sources' => [
                    'draft' => \Moox\Draft\Models\Draft::class,
                ],
            ],
        ],
    ],
],
```

Notes:

- `resource` is the Filament resource class that will be registered for this scoped child.
- `enabled` is informational only (runtime activation is controlled by DB `scopes.is_active`).
- `origin`/`boundary`/`label` are optional overrides. If omitted, the system derives defaults from keys and the parent context.
- `allowed` is the whitelist for which child origins/resources can exist under this parent.
- `registry` is metadata and must not be mixed with allowed definitions (keeps config readable).

### 5.1.1 Minimal config (recommended)

Most of the time you only need this:

```php
'resources' => [
    'draft' => [
        'scopes' => [
            'allowed' => [
                'media' => [
                    'resource' => \Moox\Media\Resources\MediaResource::class,
                ],
            ],
            'registry' => [
                'sources' => [
                    'draft' => \Moox\Draft\Models\Draft::class,
                ],
            ],
        ],
    ],
],
```

Optional keys (`origin`, `context`, `boundary`, `label`) only matter if you want to override derived defaults or provide a default row for `moox:scope`.

Important:

- `allowed` defines which child origins/resources are supported under the parent (whitelist for **`moox:scope`**, the **Scopes create form**, and resource mapping — not for panel navigation)
- navigation items come only from **active DB scopes**:
  - `moox:scope` seeds default rows from `allowed`
  - scopes created in the Scopes UI can also drive child navigation (no extra config slot per context/boundary)

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
php artisan moox:scope
```

Then activate/deactivate via the Scopes UI.

---

## 6) What "is_active" affects

- **Navigation**: scoped child nav item appears only when its scope is present and active.
- **Queries**: `ScopeQuery` applies DB guards so inactive scopes do not return data.
- **Assign options**: only active scopes are offered (both single-record select and bulk assign).

---

## 7) exact vs context (scope_match)

- `exact` → matches the full `origin:source:context:boundary`
- `context` → matches `origin:source:context:%` (boundary ignored)

Default behavior (when not explicitly set) is derived from the DB:

- if there is **more than one active boundary** for the same `origin/source/context` → default is `exact`
- else → default is `context`

---

## 8) Common workflows

### 8.1 Add a new scopable model (new origin)

You need four things:

1) **DB column** on the model table:

```php
$table->string('scope')->nullable()->index();
```

2) **`HasScopedModel` trait** on the model:

```php
use Moox\Core\Models\Concerns\HasScopedModel;

class MyModel extends Model
{
    use HasScopedModel;
}
```

3) **Registry entry** for the origin key (inside a resource config):

```php
'resources' => [
    'record' => [
        'scopes' => [
            'registry' => [
                'origins' => [
                    'record' => \Moox\Record\Models\Record::class,
                ],
            ],
        ],
    ],
],
```

4) **Resource UI integration** (required):

- Use `HasScopedChildResource` trait in the resource
- Edit: include `static::getScopeSelectField()`
- Table: include `static::getScopeTableColumn()` (toggleable, hidden by default)
- Plugin: use `ChildResourceRegistrar::registerFromParentDefinition()`

### 8.2 Allow an origin under a parent source

Example: allow `record` under `draft`:

```php
'resources' => [
    'draft' => [
        'scopes' => [
            'allowed' => [
                'record' => [
                    'resource' => \Moox\Record\Moox\Entities\Records\Record\RecordResource::class,
                ],
            ],
        ],
    ],
],
```

After that, users can create any `record:draft:<context>:<boundary>` scope in the UI.

### 8.3 Make it appear in navigation

Navigation is derived from the DB:

- Create/activate a scope row (`is_active=true`) for the desired combination.
- If you want defaults from config, run:

```bash
php artisan moox:scope
```

Then the scoped navigation item appears automatically (no extra config slot per context/boundary needed).
