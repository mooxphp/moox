# moox/tree

**`moox/tree`** is an internal Laravel/Filament package for hierarchical Eloquent models. It replaces the classic **table list view** of a Filament resource with a **two-column tree interface**:

| Area       | Function                                                            |
| ---------- | ------------------------------------------------------------------- |
| **Left**   | Hierarchical tree (selection, expand/collapse, optional drag & drop) |
| **Right**  | **Inspector** – edit the selected record                            |

Technically, the package is built on **Livewire**, **Alpine.js** (`$store.filamentTreeIndex`), **Filament Panels**, and **Action classes** for CRUD on the tree. Business logic lives in Actions (`CreateTreeNodeAction`, `UpdateTreeNodeAction`, `MoveTreeNodeAction`, `AssignTreeNodePositionAction`, …), not in the UI.

Namespace: `Moox\Tree` · Views/Config tag: `filament-tree-index`

## Quick start

This guide walks you through **5 steps** from installation to a running tree resource.

### Step 1: Install the package

```bash
composer require moox/tree:@dev
```

The package lives under `packages/tree`. In the Moox monorepo, the path repository for `packages/*` is already configured. The `TreeServiceProvider` is registered via Laravel auto-discovery and loads views, CSS (`tree.css` via FilamentAsset), and the Alpine store script. The tree and inspector run on **one** Livewire component (`TreeIndexListRecords`).

Optional publish:

```bash
php artisan vendor:publish --tag=filament-tree-index-config
php artisan filament:assets
```

### Step 2: Prepare the model

**Adjacency list** (default) – columns `parent_id`, `sort_order`, label column (default: `label`):

```php
protected $fillable = ['parent_id', 'label', 'sort_order'];
```

**Nested set** – additionally `_lft`/`_rgt`, model with Kalnoy `NodeTrait`:

```bash
composer require kalnoy/nestedset
```

### Step 3: Create the files

For each tree resource you need **exactly these files** (adjust names as needed):

```
src/
├── Models/Category.php
├── Resources/
│   ├── CategoryTreeResource.php          ← treeIndex() + getPages()
│   └── CategoryResource/
│       └── Pages/
│           ├── TreeListCategories.php    ← extends TreeIndexListRecords
│           ├── TreeInspectorCategory.php ← optional: standalone inspector route
│           ├── EditCategory.php          ← existing edit page (form source)
│           └── CreateCategory.php        ← create page (inline inspector create)
└── Plugins/
    └── CategoryTreePlugin.php            ← register resource in panel
```

### Step 4: Add the code

**`CategoryTreeResource.php`** – configuration and routing:

```php
use Illuminate\Support\Arr;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Contracts\ConfiguresTreeIndex;

class CategoryTreeResource extends CategoryResource implements ConfiguresTreeIndex
{
    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(Category::class)
            ->forwardFromResource(static::class, useFilamentTableToolbar: true)
            ->labelColumn('title')
            ->labelColumnQueryable(false)
            ->nestedSet()
            ->sortColumn('_lft')
            ->reorderable(true)
            ->inspectorPage(TreeInspectorCategory::class)
            ->labels(
                treeHeading: 'Categories',
                treeSubheading: 'Tree',
                inspectorHeading: 'Category',
                createRootLabel: 'New category',
                newRecordLabel: 'New category',
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => TreeListCategories::route('/'),
            'tree-inspector' => TreeInspectorCategory::route('/{record}/tree-inspector'),
            ...Arr::except(parent::getPages(), ['index']),
        ];
    }
}
```

**`TreeListCategories.php`** – list page (registers config on `mount()`):

```php
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

class TreeListCategories extends TreeIndexListRecords
{
    protected static string $resource = CategoryTreeResource::class;
}
```

**`TreeInspectorCategory.php`** (optional) – full Filament form on the right:

```php
use Moox\Tree\Filament\Concerns\RendersAsTreeIndexInspector;

class TreeInspectorCategory extends EditCategory
{
    use RendersAsTreeIndexInspector;

    // Important: point to the base resource (form source), not CategoryTreeResource
    protected static string $resource = CategoryResource::class;
}
```

**`CategoryTreePlugin.php`** – register resource in the Filament panel:

```php
use Filament\Contracts\Plugin;
use Filament\Panel;

class CategoryTreePlugin implements Plugin
{
    public function getId(): string
    {
        return 'category-tree';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CategoryTreeResource::class]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }
}
```

Enable the plugin in the panel provider:

```php
->plugins([
    CategoryTreePlugin::make(),
])
```

### Step 5: Verify

1. Open the panel → navigation entry for the tree resource
2. Tree on the left: create entries, expand/collapse, optionally move via drag & drop
3. **Create** in the page header opens the create form in the right inspector (no `/create` navigation) when a create route exists in `getPages()`
4. Select an entry → inspector on the right shows the edit form
5. Save → tree updates (`tree-index-record-saved` event on standalone inspector route)

---

## What goes where?

Overview of **which functionality lives in which file/class**. Tree mechanics belong in the package – consumer code only wires things up.

| What | Where | Responsibility |
| --- | --- | --- |
| `treeIndex()` | **Tree resource** (`implements ConfiguresTreeIndex`) | Columns, mode, labels, inspector, query hooks |
| `forwardFromResource()` | In `treeIndex()` | Adopt query, search, language, and filters 1:1 from the list |
| `getPages()` | **Tree resource** | `'index'` → tree list page; optional `'tree-inspector'` |
| `extends TreeIndexListRecords` | **Tree list page** | Single Livewire host; tree via `table()->content(tree-index-content)` |
| `use RendersAsTreeIndexInspector` | **Tree inspector page** (standalone route) | Slim embedded edit view, no redirect after save |
| Inline inspector form | **Package** (`InteractsWithTreeResourceInspectorForm`, `TreeInlineFormResourceAdapter`) | Renders `Resource::form()` on the list page — no consumer trait |
| Form (tabs, relations, …) | **Edit/Create pages** | Domain content; inspector inherits edit form; create route enables inline create |
| `$resource` on standalone inspector | **Tree inspector page** | Points to the **base resource** (form source), not the tree resource |
| `table()` / `getTitleColumn()` | **Base resource** (or tree resource with `forwardFromResource`) | Columns and filters for the toolbar |
| `getEloquentQuery()` | **Resource** | Scopes, policies, tenants – adopted via `forwardFromResource` |
| Panel registration | **Filament plugin** | `$panel->resources([XxxTreeResource::class])` |
| Tree CRUD, drag & drop | **Package** (`TreeIndexListRecords` + Actions) | Do not duplicate in consumers |
| Label resolution, locale, graph validation | **Package** (`TreeNodeLabelResolver`, `TreeLocale`, `TreeGraphValidator`) | Do not duplicate in consumers |
| Model columns | **Eloquent model** | `parent_id`, sort order, label; with nested set `NodeTrait` |

### Two resource patterns

| Pattern | When | Structure |
| --- | --- | --- |
| **Separate tree resource** (recommended in Moox) | Existing table resource remains, additional tree UI | `CategoryTreeResource extends CategoryResource` + dedicated plugin |
| **Single resource** | New, tree-only module | Resource implements `ConfiguresTreeIndex`, `getPages()` with TreeList + optional inspector |

In Moox: the **table resource** (`CategoryResource`) and the **tree resource** (`CategoryTreeResource`) coexist. The standalone inspector page references the table resource; the inline inspector resolves the form from `forwardFromResource()` (typically the tree resource class, which inherits `form()` from the base resource).

---

## Scope

Not a generic CMS plugin, but a **resource index UI** for hierarchical Eloquent models (categories, menus, folder structures). Reference in the project: `packages/category` (`CategoryTreeResource`, `TreeListCategories` with `HasListPageTabs`, `TreeInspectorCategory`, `CreateCategory`).

**Rule:** If a resource needs behavior that other trees could use too → implement the feature in `packages/tree` and enable it via config — do not duplicate in consumer packages.

## Requirements

- PHP ^8.3
- Laravel ^12
- Filament ^4/5
- Livewire ^3/4
- `moox/core`
- Optional: [`kalnoy/nestedset`](https://github.com/lazychaser/laravel-nestedset) ^6.0 for nested-set trees (`nestedSet()`)

## Architecture

```
Filament Resource (implements ConfiguresTreeIndex)
    └── treeIndex() → TreeIndexConfiguration
        └── TreeIndexQueryBuilder (query, search, language, siblings)

List Page (TreeIndexListRecords — one Livewire layer)
    ├── InteractsWithTreeIndexListPage (toolbar, tabs, URL state)
    ├── InteractsWithResourceTreeIndex (tree, selection, move)
    ├── InteractsWithTreeResourceInspectorForm (Resource::form inline)
    ├── TreeStructure + TreeNodeLabelResolver
    ├── Tree Actions (CRUD, position, persist)
    │   ├── CreateTreeNodeAction / CreateNestedSetTreeNodeAction (stubCreate)
    │   ├── PersistTreeResourceCreateAction / PersistTreeResourceUpdateAction
    │   ├── TreeResourcePageExecutor (page hooks server-side)
    │   ├── UpdateTreeNodeAction / MoveTreeNodeAction (+ TreeGraphValidator)
    │   ├── AssignTreeNodePositionAction
    │   └── DeleteTreeNodeAction
    └── Inspector (right, inline — no nested @livewire)
        ├── Resource::form() via inspectorForm schema
        └── otherwise → built-in form (label + parent)

Optional: Route `tree-inspector` with RendersAsTreeIndexInspector (standalone, not embedded)
```

### Support classes (internal)

| Class | Responsibility |
| --- | --- |
| `TreeIndexQueryBuilder` | `newQuery`, `siblingsQuery`, `siblingsExcept`, `applySearch`, `applyLanguage` |
| `TreeStructure` | Tree from adjacency list or nested set; `descendantIds`, `ancestorIds` |
| `TreeNodeLabelResolver` | Label column + optional fallback (`labelFallbackColumn`) |
| `TreeGraphValidator` | Cycle check (self-parent, child as parent) |
| `TreeLocale` | `resolveDefaultLocale`, `syncToRequest`, `localeCandidates` |
| `NestedSetGuard` | Checks Kalnoy `NodeTrait` on the model |
| `TreeIndexAuthorizer` | Gate ability or `auth()->check()` |
| `ResourceListForwarder` | Query/search/language from Filament resources |
| `TreeIndexSelection` | Checks whether the selected node is still visible in the filtered query |
| `TreeIndexResourcePages` | Resolves create/edit page classes of the forward resource |
| `TreeResourcePageExecutor` | Calls protected page hooks (`handleRecordCreation`, …) server-side |
| `TreeInlineFormResourceAdapter` | Wraps the forward resource for inspector form actions (no consumer trait) |

Business logic lives in **Actions**, not in Livewire or Blade. `TreeIndexListRecords` only orchestrates (auth, query, delegation).

### Package structure (what lives where?)

| Directory | Files (approx.) | Purpose |
| --- | --- | --- |
| `src/Actions/Tree/` | 9 | CRUD, move, persist (adjacency + nested set) |
| `src/Support/` | 12 | Query, locale, labels, validation, auth, inline form adapter |
| `src/Filament/Concerns/` | 12 | List, tree, selection, and inspector form traits |
| `src/Filament/Pages/` | 1 | `TreeIndexListRecords` |
| `src/Config/`, `Contracts/`, `Exceptions/` | 5 | Configuration, `ConfiguresTreeIndex`, `HostsInlineResourceForm`, errors |
| `resources/views/` | 9 Blade + 1 CSS | UI partials, Alpine store |
| `tests/` | ~30 | Pest feature/unit + fixtures |

Registry: list pages register the config under the **resource class name** (`TreeIndexConfigurationRegistry::register()` / `resolve()`).

## Installation (details)

### Composer

```bash
composer require moox/tree:@dev
```

Alternatively, PSR-4 autoload only in the root `composer.json` (without a `require` entry):

```json
"Moox\\Tree\\": "packages/tree/src/"
```

### Service provider

With `composer require`, `Moox\Tree\TreeServiceProvider` registers automatically. Manually in `bootstrap/providers.php`:

```php
Moox\Tree\TreeServiceProvider::class,
```

The provider:

- loads views under `filament-tree-index::`
- registers CSS (`resources/css/tree.css`) via FilamentAsset
- binds the Alpine store `$store.filamentTreeIndex` on every Filament panel page
- renders the language switcher in the Filament table toolbar (when `useFilamentTableToolbar: true`)
- does **not** register Livewire component aliases — the tree runs on `TreeIndexListRecords` only

### Assets

```bash
php artisan filament:assets
```

No separate Tailwind `@source` needed — layout classes (`fi-tree-*`) live in the package CSS file.

### Optional configuration

```bash
php artisan vendor:publish --tag=filament-tree-index-config
php artisan vendor:publish --tag=filament-tree-index-views
```

## Model requirements

The package requires **no custom model methods or traits** (except Kalnoy `NodeTrait` for nested set). Only columns/attributes:

### Variant A: Adjacency list (default)

| Column     | Default name | Purpose                                  |
| ---------- | ------------ | ---------------------------------------- |
| Parent FK  | `parent_id`  | `nullable`, reference to same table      |
| Sort order | `sort_order` | Sibling order                            |
| Display    | `label`      | Text in the tree (column name configurable) |

### Variant B: Nested set

Additionally **`_lft`** and **`_rgt`** with `kalnoy/nestedset` and `NodeTrait`. Tree building and moving use Kalnoy (`beforeNode` / `afterNode` / `appendToNode`).

Label from accessor or translation → `->labelColumnQueryable(false)` and optionally `->labelFallbackColumn('display_title')` (default fallback when the label column is empty).

## Connecting a resource (detailed)

### 1. Interface `ConfiguresTreeIndex`

```php
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Contracts\ConfiguresTreeIndex;

class MyTreeResource extends MyResource implements ConfiguresTreeIndex
{
    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(MyModel::class)
            ->forwardFromResource(static::class, useFilamentTableToolbar: true)
            ->labelColumn('title')
            ->reorderable(true)
            ->inspectorPage(TreeInspectorMyModel::class);
    }
}
```

### 2. `forwardFromResource()` – connection to the existing list

**Most important method for Moox integration.** Automatically adopts:

| Capability | Source |
| --- | --- |
| Base query | `Resource::getEloquentQuery()` |
| Search | `getTitleColumn()` / `applyListSearchToQuery()` |
| Language | `ResourceListForwarder::applyLanguage()` |
| Table filters | `applyFiltersToTableQuery()` on the list page |

```php
->forwardFromResource(static::class, useFilamentTableToolbar: true)
```

| Parameter | Behavior |
| --- | --- |
| `useFilamentTableToolbar: true` | Search, filters, and language switcher in the **Filament table toolbar** (1:1 like list view) |
| `useFilamentTableToolbar: false` | Custom toolbar in the tree column (`toolbarSearch`, `toolbarLanguageSwitcher`) |

Without `forwardFromResource()` you must set `modifyQuery`, `applySearchUsing`, and `applyLanguageUsing` manually.

The forwarded resource class must **not** be `final` — `TreeInlineFormResourceAdapter` generates a runtime subclass under `Filament/Resources/Generated/`.

### 3. List page

```php
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

class TreeListMyModels extends TreeIndexListRecords
{
    protected static string $resource = MyTreeResource::class;
}
```

On `mount()`, the page registers the configuration in `TreeIndexConfigurationRegistry` (key = resource class name).

#### Tabs (Moox)

Tree list pages with Moox tabs use `HasListPageTabs` as usual. The package (`InteractsWithTreeIndexListPage`) handles via Livewire `updated('activeTab')`:

- sync `tab` into the request (so `getEloquentQuery()` filters the correct tab)
- remove `?selected=` on every tab change

Also call `refreshTreeIndexConfiguration()` in `updatedActiveTab()` (see `TreeListCategories`).

```php
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

class TreeListMyModels extends TreeIndexListRecords
{
    use HasListPageTabs;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('my-package.resources.model.tabs', MyModel::class);
    }

    public function updatedActiveTab(): void
    {
        static::getResource()::setCurrentTab($this->activeTab);
        $this->tableFilters = null;
        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
        $this->resetTable();
        $this->refreshTreeIndexConfiguration();
    }
}
```

**Tab-specific query** — when the base resource has its own tab query:

```php
protected function applyForwardedListQuery(TreeIndexConfiguration $configuration): TreeIndexConfiguration
{
    if ($configuration->getSourceResourceClass() === null) {
        return $configuration;
    }

    return $configuration->modifyQuery(function (Builder $query): Builder {
        $query = MyResource::getEloquentQuery();

        return $this->applyFiltersToTableQuery($query);
    });
}
```

### 4. Routing

**With parent resource** (Moox pattern, reference `CategoryTreeResource`):

```php
use Illuminate\Support\Arr;

public static function getPages(): array
{
    return [
        'index' => TreeListMyModels::route('/'),
        'tree-inspector' => TreeInspectorMyModel::route('/{record}/tree-inspector'),
        ...Arr::except(parent::getPages(), ['index']),
    ];
}
```

**Single resource without parent** — define `getPages()` directly:

```php
public static function getPages(): array
{
    return [
        'index' => TreeListMyModels::route('/'),
        'tree-inspector' => TreeInspectorMyModel::route('/{record}/tree-inspector'),
    ];
}
```

### 5. Inspector (recommended)

```php
use Moox\Tree\Filament\Concerns\RendersAsTreeIndexInspector;

class TreeInspectorMyModel extends EditMyModel
{
    use RendersAsTreeIndexInspector;

    protected static string $resource = MyResource::class; // base resource, not tree resource
}
```

The `RendersAsTreeIndexInspector` trait (based on `RendersAsTreeIndexEmbeddedPage`):

- hides the page from navigation
- uses a slim inspector view
- suppresses redirects after save
- dispatches `tree-index-record-saved` for tree refresh

For **create/edit** in the inspector, the list page renders `Resource::form()` inline (`InteractsWithTreeResourceInspectorForm`). Persistence delegates to the configured create/edit page classes via `PersistTreeResourceCreateAction` / `PersistTreeResourceUpdateAction` and `TreeResourcePageExecutor`. Form actions without redirect are applied automatically by `TreeInlineFormResourceAdapter` — consumers do **not** add traits to their resources.

**Create modes** (right inspector):

| Config | Behavior |
| --- | --- |
| `inspectorPage()` + `forwardFromResource()` + create route in `getPages()` | Header **Create** opens the resource create form inline (`createRootNode()` → `usesResourceCreateInspector()`) |
| `->inspectorCreatePage(CreateXxx::class)` | Explicit create page instead of auto-resolve |
| `->stubCreate()` | Minimal create (label node via `CreateTreeNodeAction`), no resource form |
| No `inspectorPage()` | Built-in stub form (label + parent) for edit |

The `tree-inspector` route is recommended for policies, URL generation, and page hooks. The main path is inline on `TreeIndexListRecords` (one Livewire host).

Without `inspectorPage()`, the package shows only a **minimal form** on the right (label + parent select).

### 6. Panel registration

```php
$panel->resources([MyTreeResource::class]);
```

Via a Filament plugin (see quick start) or directly in the `PanelProvider`.

## `TreeIndexConfiguration`

| Method | Default | Meaning |
| --- | --- | --- |
| `make(Model::class)` | – | Eloquent model |
| `forwardFromResource(Resource::class, useFilamentTableToolbar: false)` | – | Query, search, language from existing resource |
| `parentColumn('parent_id')` | `parent_id` | Parent column |
| `sortColumn('sort_order')` | `sort_order` | Sort order; for nested set: `_lft` |
| `labelColumn('label')` | `label` | Display text in the tree |
| `labelColumnQueryable(false)` | `true` | `false` for accessor/translation |
| `labelFallbackColumn('display_title')` | `display_title` | Fallback attribute when label column is empty |
| `nestedSet()` | `false` | Tree from `_lft`/`_rgt` |
| `reorderable(true)` | `true` | Drag & drop |
| `inspectorPage(EditPage::class)` | `null` | Full Filament form on the right (edit) |
| `inspectorCreatePage(CreatePage::class)` | `null` | Explicit create page instead of auto-resolve |
| `stubCreate()` | `false` | Minimal create (label node) instead of resource create form |
| `filamentTableLanguageSwitcher(false)` | `true` with table toolbar | Disable language switcher in Filament toolbar |
| `modifyQuery(Closure)` | – | Custom query (without `forwardFromResource`) |
| `toolbarSearch(true)` | `false` | Search field in tree column (without table toolbar) |
| `toolbarLanguageSwitcher(true)` | `false` | Language switcher in tree column |
| `applySearchUsing(Closure)` | – | Custom search logic |
| `applyLanguageUsing(Closure)` | – | Custom language logic |
| `authorizationAbility('update')` | `null` | Gate ability; otherwise only `auth()->check()` |
| `labels(...)` | German default texts | UI texts |

Parameters of `labels()`: `treeHeading`, `treeSubheading`, `inspectorHeading`, `createRootLabel`, `saveLabel`, `newRecordLabel`, `deleteConfirmMessage`.

### Example: Simple tree (adjacency list)

```php
return TreeIndexConfiguration::make(TreeNode::class)
    ->parentColumn('parent_id')
    ->sortColumn('sort_order')
    ->labelColumn('label')
    ->reorderable(true);
```

### Example: Nested set with inspector (Moox)

```php
return TreeIndexConfiguration::make(Category::class)
    ->forwardFromResource(static::class, useFilamentTableToolbar: true)
    ->labelColumn('title')
    ->labelColumnQueryable(false)
    ->nestedSet()
    ->sortColumn('_lft')
    ->reorderable(true)
    ->inspectorPage(TreeInspectorCategory::class);
```

### Example: Manual toolbar (without `forwardFromResource`)

```php
return TreeIndexConfiguration::make(Category::class)
    ->modifyQuery(fn (Builder $query): Builder => static::getEloquentQuery())
    ->toolbarSearch()
    ->toolbarLanguageSwitcher()
    ->applySearchUsing(
        fn (Builder $query, string $search, TreeIndexConfiguration $config): Builder
            => $query->where('title', 'like', "%{$search}%")
    )
    ->applyLanguageUsing(
        fn (Builder $query, string $lang, TreeIndexConfiguration $config): Builder
            => $query->whereHas('translations', fn (Builder $q): Builder => $q->where('locale', $lang))
    );
```

## UI features

### Tree (left)

- Select entry → inspector on the right
- Create root entry via **Create** in page header actions (like `ListRecords`); opens the resource create form **on the right in the inspector** (no modal, no `/create` navigation); child entries in the inspector
- Expand all / collapse all
- Optional: move via drag & drop (`reorderable(true)`), including validation (not under self / own child)

### Inspector (right)

- With `inspectorPage`: existing edit page
- Without: label + parent, save, delete

### Toolbar

With `forwardFromResource(..., useFilamentTableToolbar: true)`:

- Tabs, filters, and search from `Resource::table()` in the Filament toolbar
- Language switcher via render hook (when `localization::lang-selector` is present)
- No table sorting or row actions — order only in the tree

## Actions

Business logic in action classes. `TreeIndexListRecords` delegates to them; parent/cycle validation via `TreeGraphValidator` in `UpdateTreeNodeAction` and `MoveTreeNodeAction`.

| Class | Responsibility |
| --- | --- |
| `CreateTreeNodeAction` | New node (adjacency list); with `nestedSet()` → `CreateNestedSetTreeNodeAction` |
| `CreateNestedSetTreeNodeAction` | New node via Kalnoy; checks `NodeTrait` via `NestedSetGuard` |
| `UpdateTreeNodeAction` | Update label/parent; validates parent assignment |
| `MoveTreeNodeAction` | Parent + sibling order; with `nestedSet()` → `MoveNestedSetTreeNodeAction` |
| `MoveNestedSetTreeNodeAction` | Move via Kalnoy |
| `AssignTreeNodePositionAction` | Sort order / nested-set position after resource create inspector |
| `PersistTreeResourceCreateAction` | Persists inline create via configured create page hooks |
| `PersistTreeResourceUpdateAction` | Persists inline edit via configured edit page hooks |
| `DeleteTreeNodeAction` | `$record->delete()` |

## Configuration

`config/filament-tree-index.php`:

```php
return [
    'authorization' => [
        'enabled' => true,
    ],
];
```

## Tests

```bash
php artisan test --compact packages/tree/tests
```

| Directory | Contents |
| --- | --- |
| `tests/Feature/` | `TreeIndexListRecords` / tree CRUD (reorder, move, auth, inline form) |
| `tests/Unit/` | `TreeIndexConfiguration`, `TreeStructure`, `TreeGraphValidator`, `TreeLocale`, `TreeInlineFormResourceAdapter`, registry |
| `tests/Support/` | Test resources, `CreatesTreeNodesTable` |
| `tests/Models/` | `TreeNode`, `NestedSetTreeNode` |

Tests use `Moox\Tree\Tests\TestCase` (extends app `Tests\TestCase`) via `tests/Pest.php`.

Test tree logic in isolation with `TestTreeIndexHost` (uses `InteractsWithResourceTreeIndex` without full Filament list page):

```php
config(['filament-tree-index.authorization.enabled' => false]);

TreeIndexConfigurationRegistry::register(
    'test-tree',
    TreeIndexConfiguration::make(TreeNode::class),
);

Livewire::test(TestTreeIndexHost::class, [
    'treeIndexConfigurationKey' => 'test-tree',
    'lang' => 'en',
    'search' => '',
])->call('createRootNode');
```

For full integration (toolbar, tabs, inline form), test `TreeIndexListRecords` subclasses in `tests/Feature/`. Pass `lang` and `search` in isolated tests so the component does not need to set the default locale first.

## Checklist for a new resource

1. Model: `parent_id` + `sort_order` **or** nested set (`NodeTrait`, `_lft`/`_rgt`)
2. For nested set: `composer require kalnoy/nestedset`
3. `composer require moox/tree:@dev` + `php artisan filament:assets`
4. **Tree resource**: `implements ConfiguresTreeIndex` + `treeIndex()` with `forwardFromResource()`
5. **Tree list page**: `extends TreeIndexListRecords`
6. **getPages()**: `'index'` → TreeList; optional `'tree-inspector'`
7. **Inspector** (optional): `extends EditPage` + `RendersAsTreeIndexInspector` + `inspectorPage()` in config; ensure create route in `getPages()` for inline create
8. Forward resource is **not** `final` (inline form adapter)
9. **Plugin**: register resource in Filament panel
10. Feature test for config, `TreeIndexListRecords`, or `TestTreeIndexHost`

## License

MIT
