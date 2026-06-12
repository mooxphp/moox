# Integration — Code Templates & Checklist

Placeholder: `Xxx` = domain (e.g. `Category`), `XxxResource` = base resource, `XxxTreeResource` = tree resource.

## Prerequisites

Complete **[installation.md](installation.md)** first (Composer, service provider, `php artisan filament:assets`, optional `kalnoy/nestedset` / `moox/localization`).

Minimum commands:

```bash
composer require moox/tree:@dev
php artisan filament:assets
```

Do **not** add tree CSS, Alpine store scripts, or custom Livewire components in the consumer — assets and the tree host (`TreeIndexListRecords`) come from `TreeServiceProvider` / the package only.

---

## Toolbar vor der Implementierung (Pflicht)

Siehe [decisions.md §6](decisions.md#6-toolbar-suche-filter-sprach-switcher). **Vor** dem Anlegen von `XxxTreeResource` / `treeIndex()`:

1. `AskQuestion` mit drei Ja/Nein-Fragen: **Suche**, **Filter/Tabs**, **Sprach-Switcher**
2. Zeile aus der Entscheidungsmatrix in §6 wählen
3. Erst dann Code aus den Templates unten anpassen

---

## Muster A — Separate Tree-Resource (Moox-Standard)

### Dateistruktur

```
packages/{package}/src/
├── Resources/
│   ├── XxxTreeResource.php
│   └── XxxResource/Pages/
│       ├── TreeListXxx.php
│       ├── TreeInspectorXxx.php    # optional
│       └── EditXxx.php             # Formular-Quelle für Inspector
└── Plugins/
    └── XxxTreePlugin.php
```

### XxxTreeResource.php

```php
<?php

declare(strict_types=1);

namespace Moox\Xxx\Resources;

use Illuminate\Support\Arr;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Xxx\Models\Xxx;
use Moox\Xxx\Resources\XxxResource\Pages\TreeInspectorXxx;
use Moox\Xxx\Resources\XxxResource\Pages\TreeListXxx;

class XxxTreeResource extends XxxResource implements ConfiguresTreeIndex
{
    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(Xxx::class)
            ->forwardFromResource(static::class, useFilamentTableToolbar: true)
            ->labelColumn('title')              // anpassen
            ->labelColumnQueryable(false)       // bei Accessor/Translation
            ->nestedSet()                       // nur bei Nested Set
            ->sortColumn('_lft')                // bei Nested Set; sonst sort_order
            ->reorderable(true)
            ->inspectorPage(TreeInspectorXxx::class)  // optional
            ->labels(
                treeHeading: '...',
                treeSubheading: 'Baum',
                inspectorHeading: '...',
                createRootLabel: '...',
                newRecordLabel: '...',
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => TreeListXxx::route('/'),
            'tree-inspector' => TreeInspectorXxx::route('/{record}/tree-inspector'),
            ...Arr::except(parent::getPages(), ['index']),
        ];
    }
}
```

### TreeListXxx.php

```php
<?php

declare(strict_types=1);

namespace Moox\Xxx\Resources\XxxResource\Pages;

use Moox\Tree\Filament\Pages\TreeIndexListRecords;
use Moox\Xxx\Resources\XxxTreeResource;

class TreeListXxx extends TreeIndexListRecords
{
    protected static string $resource = XxxTreeResource::class;
}
```

### Mit Tabs (optional)

```php
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Xxx\Models\Xxx;

class TreeListXxx extends TreeIndexListRecords
{
    use HasListPageTabs;

    protected static string $resource = XxxTreeResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('xxx.resources.xxx.tabs', Xxx::class);
    }

    public function updatedActiveTab(): void
    {
        static::getResource()::setCurrentTab($this->activeTab);
        $this->resetTable();
        $this->refreshTreeIndexConfiguration();
    }
}
```

`InteractsWithTreeIndexListPage` syncs `tab` into the request and clears `?selected=` via Livewire `updated('activeTab')`. Call `refreshTreeIndexConfiguration()` in `updatedActiveTab()` so the tree query matches the active tab; reset table state when tabs affect filters (see `TreeListCategories`: `tableFilters`, `tableSortColumn`, `resetTable()`). Reference: `TreeListCategories`.

### TreeInspectorXxx.php (optional)

```php
<?php

declare(strict_types=1);

namespace Moox\Xxx\Resources\XxxResource\Pages;

use Moox\Tree\Filament\Concerns\RendersAsTreeIndexInspector;
use Moox\Xxx\Resources\XxxResource;

class TreeInspectorXxx extends EditXxx
{
    use RendersAsTreeIndexInspector;

    protected static string $resource = XxxResource::class; // Basis-Resource, nicht XxxTreeResource
}
```

`RendersAsTreeIndexInspector` baut auf `RendersAsTreeIndexEmbeddedPage` (schlanke View, keine Redirects nach Save) und `InteractsWithTreeIndexInspectorLocale` auf — für die Route `tree-inspector` und als Edit-Page-Klasse für Page-Hooks.

**Create/Edit im eingebetteten Inspector:** `TreeIndexListRecords` rendert `Resource::form()` inline in `tree-index-content` (kein nested `@livewire`). Form-Actions (Save/Cancel ohne Redirect) wendet das Package automatisch per `TreeInlineFormResourceAdapter` an — **kein** Trait auf der Consumer-Resource. Persistenz delegiert an die normale Create-/Edit-Page via `PersistTreeResourceCreateAction` / `PersistTreeResourceUpdateAction` und `TreeResourcePageExecutor`.

**Create-Modi** (rechter Inspector):

| Config | Verhalten |
|--------|-----------|
| `inspectorPage()` + `forwardFromResource()` + Create-Route in `getPages()` | Header-**Create** öffnet das Resource-Create-Formular inline (`createRootNode()` → `usesResourceCreateInspector()`) |
| `->inspectorCreatePage(CreateXxx::class)` | Explizite Create-Page statt Auto-Resolve |
| `->stubCreate()` | Minimal-Create (Label-Knoten via `CreateTreeNodeAction`), kein Resource-Formular |
| Kein `inspectorPage()` | Eingebautes Stub-Formular (Label + Parent) für Edit |

**Hinweis:** Die an `forwardFromResource()` übergebene Resource-Klasse darf nicht `final` sein (der Adapter erzeugt eine interne Subklasse unter `Filament/Resources/Generated/`).

### XxxTreePlugin.php

```php
<?php

declare(strict_types=1);

namespace Moox\Xxx\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Xxx\Resources\XxxTreeResource;

class XxxTreePlugin implements Plugin
{
    public function getId(): string
    {
        return 'xxx-tree';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            XxxTreeResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }
}
```

Panel-Provider:

```php
->plugins([
    XxxTreePlugin::make(),
])
```

---

## Muster B — Eine Resource ohne Parent

```php
<?php

declare(strict_types=1);

namespace Moox\Xxx\Resources;

use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Xxx\Models\Xxx;
use Moox\Xxx\Resources\XxxResource\Pages\TreeInspectorXxx;
use Moox\Xxx\Resources\XxxResource\Pages\TreeListXxx;

class XxxResource extends Resource implements ConfiguresTreeIndex
{
    public static function getPages(): array
    {
        return [
            'index' => TreeListXxx::route('/'),
            'tree-inspector' => TreeInspectorXxx::route('/{record}/tree-inspector'),
        ];
    }

    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(Xxx::class)
            ->parentColumn('parent_id')
            ->sortColumn('sort_order')
            ->labelColumn('label')
            ->reorderable(true);
    }
}
```

`TreeListXxx` zeigt auf `XxxResource::class` (nicht separate Tree-Resource).

---

## treeIndex() nach Toolbar-Wahl (§6)

### Suche + Filter + Sprache (Category-Pattern)

```php
return TreeIndexConfiguration::make(Xxx::class)
    ->forwardFromResource(static::class, useFilamentTableToolbar: true)
    ->labelColumn('title')
    ->labelColumnQueryable(false)
    ->nestedSet()
    ->sortColumn('_lft')
    ->reorderable(true)
    ->inspectorPage(TreeInspectorXxx::class);
```

### Suche + Filter, ohne Sprache (User-Pattern)

```php
return TreeIndexConfiguration::make(Xxx::class)
    ->forwardFromResource(static::class, useFilamentTableToolbar: true)
    ->filamentTableLanguageSwitcher(false)
    ->parentColumn('parent_id')
    ->sortColumn('sort_order')
    ->labelColumn('name')
    ->reorderable(true)
    ->inspectorPage(TreeInspectorXxx::class);

// Auf derselben Resource — Pflicht wenn keine getTitleColumn():
public static function applyListSearchToQuery(Builder $query, string $search): Builder { ... }
```

### Nur Baum, ohne Toolbar (Suche/Filter/Sprache alle Nein)

```php
use Illuminate\Database\Eloquent\Builder;

return TreeIndexConfiguration::make(Xxx::class)
    ->modifyQuery(fn (Builder $query): Builder => static::getEloquentQuery())
    ->parentColumn('parent_id')
    ->sortColumn('sort_order')
    ->labelColumn('label')
    ->reorderable(true);
```

### Manuell (ohne forwardFromResource, mit eigener Baum-Toolbar)

```php
use Illuminate\Database\Eloquent\Builder;

return TreeIndexConfiguration::make(Xxx::class)
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

---

## Verification checklist

After every integration:

- [ ] [installation.md](installation.md) complete: `moox/tree` in Composer, provider discovered, `php artisan filament:assets` run; tree renders styled in browser
- [ ] No duplicate tree CSS/JS/Alpine store in consumer package
- [ ] Model columns match chosen tree mode (adjacency or nested set + `NodeTrait`)
- [ ] Toolbar choice documented: search / filters / language (§6 — via `AskQuestion` or explicit user input)
- [ ] Resource: `implements ConfiguresTreeIndex` + `treeIndex()` with path from [decisions.md](decisions.md)
- [ ] If search yes: `getTitleColumn()` or `applyListSearchToQuery()` present
- [ ] If language no + Filament toolbar: `filamentTableLanguageSwitcher(false)` set
- [ ] `TreeListXxx extends TreeIndexListRecords` with correct `$resource`
- [ ] Create button in page header (`getHeaderActions()` on `TreeIndexListRecords` calls `createRootNode()` — no modal, no `/create` navigation); with `inspectorPage` + `forwardFromResource` + Create route in `getPages()`, opens the resource create form in the right inspector
- [ ] `getPages()`: `'index'` → TreeList; inspector route `'tree-inspector'` when used
- [ ] Inspector (if used): `$resource` = base resource; trait `RendersAsTreeIndexInspector`
- [ ] `XxxTreePlugin` registers tree resource in panel (pattern A)
- [ ] With tabs: `HasListPageTabs` + `updatedActiveTab()` calling `refreshTreeIndexConfiguration()` (see `TreeListCategories`)
- [ ] No duplicated tree mechanics in consumer (no custom Livewire/move actions)
- [ ] No model traits/methods added only for tree

Further API details: `packages/tree/README.md`
