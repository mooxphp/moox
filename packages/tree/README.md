# moox/tree

**`moox/tree`** ist ein internes Laravel-/Filament-Package für hierarchische Eloquent-Modelle. Es ersetzt die klassische **Tabellen-Listenansicht** eines Filament-Resources durch eine **zweispaltige Baum-Oberfläche**:

| Bereich    | Funktion                                                            |
| ---------- | ------------------------------------------------------------------- |
| **Links**  | Hierarchischer Baum (Auswahl, Auf-/Zuklappen, optional Drag & Drop) |
| **Rechts** | **Inspector** – Bearbeitung des gewählten Datensatzes               |

Technisch basiert das Package auf **Livewire**, **Alpine.js** (`$store.filamentTreeIndex`), **Filament Panels** und **Action-Klassen** für CRUD am Baum. Geschäftslogik liegt in Actions (`CreateTreeNodeAction`, `UpdateTreeNodeAction`, …), nicht in der UI.

Namespace: `Moox\Tree` · Views/Config-Tag: `filament-tree-index`

## Schnellstart

Diese Anleitung führt dich in **5 Schritten** von der Installation bis zur laufenden Baum-Resource.

### Schritt 1: Package einbinden

```bash
composer require moox/tree:@dev
```

Das Package liegt unter `packages/tree`. Im Moox-Monorepo ist das Path-Repository für `packages/*` bereits konfiguriert. Der `TreeServiceProvider` wird per Laravel Auto-Discovery registriert und lädt Views, CSS (`tree.css` via FilamentAsset), die Livewire-Komponente `ResourceTreeIndex` sowie das Alpine-Store-Script.

Optional publishen:

```bash
php artisan vendor:publish --tag=filament-tree-index-config
php artisan filament:assets
```

### Schritt 2: Model vorbereiten

**Adjacency List** (Standard) – Spalten `parent_id`, `sort_order`, Label-Spalte (Default: `label`):

```php
protected $fillable = ['parent_id', 'label', 'sort_order'];
```

**Nested Set** – zusätzlich `_lft`/`_rgt`, Model mit Kalnoy `NodeTrait`:

```bash
composer require kalnoy/nestedset
```

### Schritt 3: Dateien anlegen

Für jede Baum-Resource brauchst du **genau diese Dateien** (Namen anpassen):

```
src/
├── Models/Category.php
├── Resources/
│   ├── CategoryTreeResource.php          ← treeIndex() + getPages()
│   └── CategoryResource/
│       └── Pages/
│           ├── TreeListCategories.php    ← extends TreeIndexListRecords
│           ├── TreeInspectorCategory.php ← optional: Inspector-Formular
│           └── EditCategory.php          ← bestehende Edit-Page (Formular-Quelle)
└── Plugins/
    └── CategoryTreePlugin.php            ← Resource im Panel registrieren
```

### Schritt 4: Code einfügen

**`CategoryTreeResource.php`** – Konfiguration und Routing:

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
                treeHeading: 'Kategorien',
                treeSubheading: 'Baum',
                inspectorHeading: 'Kategorie',
                createRootLabel: 'Neue Kategorie',
                createChildLabel: 'Unterkategorie',
                newRecordLabel: 'Neue Kategorie',
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

**`TreeListCategories.php`** – List-Page (registriert die Config beim `mount()`):

```php
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

class TreeListCategories extends TreeIndexListRecords
{
    protected static string $resource = CategoryTreeResource::class;
}
```

**`TreeInspectorCategory.php`** (optional) – volles Filament-Formular rechts:

```php
use Moox\Tree\Filament\Concerns\RendersAsTreeIndexInspector;

class TreeInspectorCategory extends EditCategory
{
    use RendersAsTreeIndexInspector;

    // Wichtig: auf die Basis-Resource zeigen (Formular-Quelle), nicht auf CategoryTreeResource
    protected static string $resource = CategoryResource::class;
}
```

**`CategoryTreePlugin.php`** – Resource im Filament-Panel registrieren:

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

Plugin im Panel-Provider aktivieren:

```php
->plugins([
    CategoryTreePlugin::make(),
])
```

### Schritt 5: Prüfen

1. Panel öffnen → Navigationseintrag der Tree-Resource
2. Baum links: Einträge anlegen, auf-/zuklappen, optional per Drag & Drop verschieben
3. Eintrag wählen → Inspector rechts zeigt das Formular
4. Speichern → Baum aktualisiert sich (Event `tree-index-record-saved`)

---

## Was gehört wohin?

Übersicht, **welche Funktion in welcher Datei/Klasse** liegt. Tree-Mechanik gehört ins Package – Consumer-Code nur verdrahten.

| Was | Wo | Aufgabe |
| --- | --- | --- |
| `treeIndex()` | **Tree-Resource** (`implements ConfiguresTreeIndex`) | Spalten, Modus, Labels, Inspector, Query-Hooks |
| `forwardFromResource()` | In `treeIndex()` | Query, Suche, Sprache und Filter 1:1 von der Liste übernehmen |
| `getPages()` | **Tree-Resource** | `'index'` → Tree-List-Page; optional `'tree-inspector'` |
| `extends TreeIndexListRecords` | **TreeList-Page** | Registriert Config in Registry; rendert Baum via EmbeddedTable |
| `use RendersAsTreeIndexInspector` | **TreeInspector-Page** | Schlanke eingebettete Edit-View, kein Redirect nach Save |
| Formular (Tabs, Relationen, …) | **Edit-Page** (Parent von Inspector) | Domänen-Inhalt; Inspector erbt davon |
| `$resource` am Inspector | **TreeInspector-Page** | Zeigt auf die **Basis-Resource** (Formular-Quelle), nicht die Tree-Resource |
| `table()` / `getTitleColumn()` | **Basis-Resource** (oder Tree-Resource bei `forwardFromResource`) | Spalten und Filter für die Toolbar |
| `getEloquentQuery()` | **Resource** | Scopes, Policies, Mandanten – wird via `forwardFromResource` übernommen |
| Panel-Registrierung | **Filament-Plugin** | `$panel->resources([XxxTreeResource::class])` |
| Baum-CRUD, Drag & Drop | **Package** (`ResourceTreeIndex`, Actions) | Nicht im Consumer duplizieren |
| Model-Spalten | **Eloquent-Model** | `parent_id`, Sortierung, Label; bei Nested Set `NodeTrait` |

### Zwei Resource-Muster

| Muster | Wann | Aufbau |
| --- | --- | --- |
| **Separate Tree-Resource** (empfohlen bei Moox) | Bestehende Tabellen-Resource bleibt, zusätzliche Baum-UI | `CategoryTreeResource extends CategoryResource` + eigenes Plugin |
| **Eine Resource** | Neues, nur-baumbasiertes Modul | Resource implementiert `ConfiguresTreeIndex`, Trait `ConfiguresTreeIndex` für Routing |

Bei Moox: Die **Tabellen-Resource** (`CategoryResource`) und die **Baum-Resource** (`CategoryTreeResource`) koexistieren. Der Inspector verweist auf die Tabellen-Resource, weil dort das vollständige Formular definiert ist.

---

## Abgrenzung

Kein generisches CMS-Plugin, sondern eine **Resource-Index-UI** für hierarchische Eloquent-Modelle (Kategorien, Menüs, Ordnerstrukturen). Referenz im Projekt: `packages/category` (`CategoryTreeResource`, `TreeListCategories`, `TreeInspectorCategory`).

**Regel:** Braucht eine Resource ein Verhalten, das andere Bäume auch nutzen könnten → Feature ins `packages/tree` legen und per Config aktivieren — nicht in Consumer-Packages duplizieren.

## Anforderungen

- PHP ^8.3
- Laravel ^12
- Filament ^4/5
- Livewire ^3/4
- `moox/core`
- Optional: [`kalnoy/nestedset`](https://github.com/lazychaser/laravel-nestedset) ^6.0 für Nested-Set-Bäume (`nestedSet()`)

## Architektur

```
Filament Resource (ConfiguresTreeIndex)
    └── treeIndex() → TreeIndexConfiguration
List Page (TreeIndexListRecords)
    └── EmbeddedTable + tree-content
        └── Livewire ResourceTreeIndex
            ├── TreeStructure (Baumaufbau)
            ├── Tree Actions (CRUD)
            └── Inspector
                ├── inspectorPage → Edit-Page mit RendersAsTreeIndexInspector
                └── sonst → eingebautes Formular (Label + Parent)
```

## Installation (Details)

### Composer

```bash
composer require moox/tree:@dev
```

Alternativ nur PSR-4-Autoload in der Root-`composer.json` (ohne `require`-Eintrag):

```json
"Moox\\Tree\\": "packages/tree/src/"
```

### Service Provider

Bei `composer require` registriert sich `Moox\Tree\TreeServiceProvider` automatisch. Manuell in `bootstrap/providers.php`:

```php
Moox\Tree\TreeServiceProvider::class,
```

Der Provider:

- lädt Views unter `filament-tree-index::`
- registriert CSS (`resources/css/tree.css`) via FilamentAsset
- registriert Livewire `ResourceTreeIndex`
- bindet Alpine-Store `$store.filamentTreeIndex` ein
- rendert Language-Switcher in der Filament-Table-Toolbar (wenn `useFilamentTableToolbar: true`)

### Assets

```bash
php artisan filament:assets
```

Kein separates Tailwind-`@source` nötig — Layout-Klassen (`fi-tree-*`) liegen in der Package-CSS-Datei.

### Optionale Konfiguration

```bash
php artisan vendor:publish --tag=filament-tree-index-config
php artisan vendor:publish --tag=filament-tree-index-views
```

## Voraussetzungen am Model

Das Package verlangt **keine eigenen Model-Methoden oder Traits** (außer Kalnoy `NodeTrait` bei Nested Set). Nur Spalten/Attribute:

### Variante A: Adjacency List (Standard)

| Spalte     | Standardname | Zweck                                  |
| ---------- | ------------ | -------------------------------------- |
| Eltern-FK  | `parent_id`  | `nullable`, Verweis auf eigene Tabelle |
| Sortierung | `sort_order` | Geschwister-Reihenfolge                |
| Anzeige    | `label`      | Text im Baum (Spaltenname konfigurierbar) |

### Variante B: Nested Set

Zusätzlich **`_lft`** und **`_rgt`** mit `kalnoy/nestedset` und `NodeTrait`. Baumaufbau und Verschieben laufen über Kalnoy (`beforeNode` / `afterNode` / `appendToNode`).

Label aus Accessor oder Translation → `->labelColumnQueryable(false)` setzen.

## Resource anbinden (ausführlich)

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

### 2. `forwardFromResource()` – Verbindung zur bestehenden Liste

**Wichtigste Methode für die Moox-Integration.** Übernimmt automatisch:

| Fähigkeit | Quelle |
| --- | --- |
| Basis-Query | `Resource::getEloquentQuery()` |
| Suche | `getTitleColumn()` / `applyListSearchToQuery()` |
| Sprache | `ResourceListForwarder::applyLanguage()` |
| Tabellen-Filter | `applyFiltersToTableQuery()` auf der List-Page |

```php
->forwardFromResource(static::class, useFilamentTableToolbar: true)
```

| Parameter | Verhalten |
| --- | --- |
| `useFilamentTableToolbar: true` | Suche, Filter und Language-Switcher in der **Filament-Tabellen-Toolbar** (1:1 wie Listenansicht) |
| `useFilamentTableToolbar: false` | Eigene Toolbar in der Baum-Spalte (`toolbarSearch`, `toolbarLanguageSwitcher`) |

Ohne `forwardFromResource()` musst du `modifyQuery`, `applySearchUsing` und `applyLanguageUsing` manuell setzen.

### 3. List-Page

```php
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

class TreeListMyModels extends TreeIndexListRecords
{
    protected static string $resource = MyTreeResource::class;
}
```

Beim `mount()` registriert die Page die Konfiguration in der `TreeIndexConfigurationRegistry` (Schlüssel = Resource-Klassenname).

#### Tabs (Moox)

Wenn die List-Page Tabs nutzt (`HasListPageTabs`), bei Tab-Wechsel die Config neu laden:

```php
public function updatedActiveTab(): void
{
    static::getResource()::setCurrentTab($this->activeTab);
    $this->tableFilters = null;
    $this->resetTable();
    $this->refreshTreeIndexConfiguration();
}
```

### 4. Routing

**Mit Parent-Resource** (Moox-Muster):

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

**Schlanke Resource** – Trait-Shortcut:

```php
use Moox\Tree\Filament\Concerns\ConfiguresTreeIndex;

class MyTreeResource extends Resource implements ConfiguresTreeIndex
{
    use ConfiguresTreeIndex;

    protected static function getTreeIndexListPage(): string
    {
        return TreeListMyModels::class;
    }

    protected static function getAdditionalResourcePages(): array
    {
        return [
            'tree-inspector' => TreeInspectorMyModel::route('/{record}/tree-inspector'),
        ];
    }
}
```

### 5. Inspector (empfohlen)

```php
use Moox\Tree\Filament\Concerns\RendersAsTreeIndexInspector;

class TreeInspectorMyModel extends EditMyModel
{
    use RendersAsTreeIndexInspector;

    protected static string $resource = MyResource::class; // Basis-Resource, nicht Tree-Resource
}
```

Das Trait:

- blendet die Page aus der Navigation aus
- nutzt eine schlanke Inspector-View
- unterdrückt Redirects nach dem Speichern
- dispatcht `tree-index-record-saved` für Baum-Aktualisierung

Die Route `tree-inspector` ist empfohlen (Policies, URL-Generierung). Der Inspector wird eingebettet per Livewire in der Index-Page.

Ohne `inspectorPage()` zeigt das Package rechts nur ein **Minimalformular** (Label + Parent-Select).

### 6. Panel-Registrierung

```php
$panel->resources([MyTreeResource::class]);
```

Über ein Filament-Plugin (siehe Schnellstart) oder direkt im `PanelProvider`.

## `TreeIndexConfiguration`

| Methode | Default | Bedeutung |
| --- | --- | --- |
| `make(Model::class)` | – | Eloquent-Model |
| `forwardFromResource(Resource::class, useFilamentTableToolbar: false)` | – | Query, Suche, Sprache von bestehender Resource |
| `parentColumn('parent_id')` | `parent_id` | Eltern-Spalte |
| `sortColumn('sort_order')` | `sort_order` | Sortierung; bei Nested Set: `_lft` |
| `labelColumn('label')` | `label` | Anzeigetext im Baum |
| `labelColumnQueryable(false)` | `true` | `false` bei Accessor/Translation |
| `nestedSet()` | `false` | Baum aus `_lft`/`_rgt` |
| `reorderable(true)` | `true` | Drag & Drop |
| `inspectorPage(EditPage::class)` | `null` | Volles Filament-Formular rechts |
| `modifyQuery(Closure)` | – | Eigene Query (ohne `forwardFromResource`) |
| `toolbarSearch(true)` | `false` | Suchfeld in Baum-Spalte (ohne Table-Toolbar) |
| `toolbarLanguageSwitcher(true)` | `false` | Language-Switcher in Baum-Spalte |
| `applySearchUsing(Closure)` | – | Eigene Suchlogik |
| `applyLanguageUsing(Closure)` | – | Eigene Sprachlogik |
| `authorizationAbility('update')` | `null` | Gate-Ability; sonst nur `auth()->check()` |
| `labels(...)` | deutsche Standardtexte | UI-Texte |

Parameter von `labels()`: `treeHeading`, `treeSubheading`, `inspectorHeading`, `createRootLabel`, `createChildLabel`, `saveLabel`, `newRecordLabel`, `deleteConfirmMessage`.

### Beispiel: Einfacher Baum (Adjacency List)

```php
return TreeIndexConfiguration::make(TreeNode::class)
    ->parentColumn('parent_id')
    ->sortColumn('sort_order')
    ->labelColumn('label')
    ->reorderable(true);
```

### Beispiel: Nested Set mit Inspector (Moox)

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

### Beispiel: Manuelle Toolbar (ohne `forwardFromResource`)

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

## UI-Funktionen

### Baum (links)

- Eintrag wählen → Inspector rechts
- Root-Eintrag und Untereinträge anlegen
- Alle aufklappen / einklappen
- Optional: Verschieben per Drag & Drop (`reorderable(true)`), inkl. Validierung (nicht unter sich selbst / eigenes Kind)

### Inspector (rechts)

- Mit `inspectorPage`: bestehende Edit-Page
- Ohne: Label + Parent, Speichern, Löschen

### Toolbar

Mit `forwardFromResource(..., useFilamentTableToolbar: true)`:

- Tabs, Filter und Suche aus `Resource::table()` in der Filament-Toolbar
- Language-Switcher via Render-Hook (wenn `localization::lang-selector` vorhanden)
- Keine Tabellen-Sortierung oder Zeilen-Actions — Reihenfolge nur im Baum

## Actions

Geschäftslogik in Action-Klassen. `ResourceTreeIndex` delegiert dorthin.

| Klasse | Aufgabe |
| --- | --- |
| `CreateTreeNodeAction` | Neuer Knoten (Adjacency List); bei `nestedSet()` → `CreateNestedSetTreeNodeAction` |
| `CreateNestedSetTreeNodeAction` | Neuer Knoten per Kalnoy; Model braucht `NodeTrait` |
| `UpdateTreeNodeAction` | Label/Parent aktualisieren |
| `MoveTreeNodeAction` | Parent + Geschwister-Reihenfolge; bei `nestedSet()` → `MoveNestedSetTreeNodeAction` |
| `MoveNestedSetTreeNodeAction` | Verschieben per Kalnoy |
| `DeleteTreeNodeAction` | `$record->delete()` |

## Konfiguration

`config/filament-tree-index.php`:

```php
return [
    'authorization' => [
        'enabled' => true,
    ],
    'livewire' => [
        'alias' => 'filament-tree-index',
    ],
];
```

## Tests

```bash
php artisan test --compact packages/tree/tests
```

| Verzeichnis | Inhalt |
| --- | --- |
| `tests/Feature/` | Livewire `ResourceTreeIndex` (CRUD, Reorder) |
| `tests/Unit/` | `TreeIndexConfiguration`, `TreeStructure`, Registry |
| `tests/Support/` | Test-Resources, Forward-Konfiguration |
| `tests/Models/` | `TreeNode`, `NestedSetTreeNode` |

Livewire direkt testen:

```php
config(['filament-tree-index.authorization.enabled' => false]);

TreeIndexConfigurationRegistry::register(
    'test-tree',
    TreeIndexConfiguration::make(TreeNode::class),
);

Livewire::test(ResourceTreeIndex::class, ['configurationKey' => 'test-tree'])
    ->call('createRootNode');
```

## Checkliste für eine neue Resource

1. Model: `parent_id` + `sort_order` **oder** Nested Set (`NodeTrait`, `_lft`/`_rgt`)
2. Bei Nested Set: `composer require kalnoy/nestedset`
3. `composer require moox/tree:@dev` + `php artisan filament:assets`
4. **Tree-Resource**: `implements ConfiguresTreeIndex` + `treeIndex()` mit `forwardFromResource()`
5. **TreeList-Page**: `extends TreeIndexListRecords`
6. **getPages()**: `'index'` → TreeList; optional `'tree-inspector'`
7. **Inspector** (optional): `extends EditPage` + `RendersAsTreeIndexInspector` + `inspectorPage()` in Config
8. **Plugin**: Resource im Filament-Panel registrieren
9. Feature-Test für Config oder Livewire `ResourceTreeIndex`

## Lizenz

MIT
