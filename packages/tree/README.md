# filament-tree-index

**`heco/filament-tree-index`** ist ein internes Laravel-/Filament-5-Package für hierarchische Eloquent-Modelle. Es ersetzt die klassische **Tabellen-Listenansicht** eines Filament-Resources durch eine **zweispaltige Baum-Oberfläche**:

| Bereich | Funktion |
|--------|----------|
| **Links** | Hierarchischer Baum (Auswahl, Auf-/Zuklappen, optional Drag & Drop) |
| **Rechts** | **Inspector** – Bearbeitung des gewählten Datensatzes |

Technisch basiert das Package auf **Livewire 4**, **Alpine.js** (`$store.filamentTreeIndex`), **Filament Panels** und **Action-Klassen** für CRUD am Baum. Geschäftslogik liegt in Actions (`CreateTreeNodeAction`, `UpdateTreeNodeAction`, …), nicht in der UI.

## Abgrenzung

Kein generisches CMS-Plugin, sondern eine **Resource-Index-UI** für hierarchische Eloquent-Modelle (Kategorien, Menüs, Ordnerstrukturen). Im Projekt wird es z. B. für `CategoryResource` genutzt. Ähnlich aufgebaut ist `menu-builder`; dieses Package ist auf **Filament-Resources** mit `TreeIndexListRecords` zugeschnitten.

## Anforderungen

- PHP ^8.3
- Laravel ^12
- Filament ^5
- Livewire ^4
- Optional: [`kalnoy/nestedset`](https://github.com/lazychaser/laravel-nestedset) ^6.0 für Nested-Set-Bäume (`nestedSet()`)

## Architektur

```
Filament Resource (ConfiguresTreeIndex)
    └── treeIndex() → TreeIndexConfiguration
List Page (TreeIndexListRecords)
    └── tree-index.blade.php
        └── Livewire ResourceTreeIndex
            ├── TreeStructure (Baumaufbau)
            ├── Tree Actions (CRUD)
            └── Inspector
                ├── inspectorPage → Edit-Page mit RendersAsTreeIndexInspector
                └── sonst → eingebautes Formular (Label + Parent)
```

## Installation

### 1. Composer (Path-Repository)

Das Package liegt unter `packages/filament-tree-index`. Im Host-Projekt sind Path-Repositories für `packages/*` üblich. Package einbinden:

```bash
composer require heco/filament-tree-index:@dev
```

Alternativ nur PSR-4-Autoload in der Root-`composer.json` (ohne `require`-Eintrag):

```json
"Heco\\FilamentTreeIndex\\": "packages/filament-tree-index/src/"
```

### 2. Service Provider

Bei `composer require` erfolgt die Registrierung über `extra.laravel.providers` in der Package-`composer.json` (Auto-Discovery).

Ohne Composer-Abhängigkeit manuell in `bootstrap/providers.php`:

```php
use Heco\FilamentTreeIndex\FilamentTreeIndexServiceProvider;

FilamentTreeIndexServiceProvider::class,
```

Der Provider lädt Views, registriert die Livewire-Komponente `ResourceTreeIndex` und bindet das Alpine-Store-Script (`$store.filamentTreeIndex`) per Filament-Render-Hook ein.

### 3. Tailwind-Quellen im Filament-Admin-Theme

Damit Layout-Hilfsklassen aus den Blade-Views erkannt werden (z. B. für Scroll-Höhen), die Package-Views in die Theme-`@source`-Direktive aufnehmen:

```css
@source "../../../../packages/filament-tree-index/resources/views/**/*.blade.php";
```

(z. B. in `resources/css/filament/admin/theme.css` – Pfad relativ zur Theme-Datei anpassen)

Nach Änderungen am Theme: `npm run build` oder `npm run dev`.

Styling erfolgt ausschließlich über **Filament-Komponenten** (`x-filament::section`, `x-filament::button`, `x-filament::icon-button`, `x-filament::empty-state`, …) und deren `fi-*`-Klassen – es gibt keine separate Package-CSS-Datei.

### 4. Optionale Konfiguration

```bash
php artisan vendor:publish --tag=filament-tree-index-config
```

Views publishen:

```bash
php artisan vendor:publish --tag=filament-tree-index-views
```

## Voraussetzungen am Model

### Variante A: Adjacency List (Standard)

| Spalte | Standardname | Zweck |
|--------|--------------|--------|
| Eltern-FK | `parent_id` | `nullable`, Verweis auf eigene Tabelle |
| Sortierung | `sort_order` | Geschwister-Reihenfolge |
| Anzeige | `label` | Text im Baum |

```php
protected $fillable = ['parent_id', 'label', 'sort_order'];
```

### Variante B: Nested Set

Zusätzlich **`_lft`** und **`_rgt`** (z. B. mit `kalnoy/nestedset`, `NodeTrait` am Model). Baumaufbau und Verschieben laufen über Kalnoy (`beforeNode` / `afterNode` / `appendToNode`); **`parent_id`**, **`_lft`** und **`_rgt`** werden dabei korrekt gesetzt.

## Resource anbinden

### Schritt 1: Interface `ConfiguresTreeIndex` implementieren

Die Resource muss `treeIndex()` bereitstellen (Contract: `Heco\FilamentTreeIndex\Contracts\ConfiguresTreeIndex`).

```php
use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;
use Heco\FilamentTreeIndex\Contracts\ConfiguresTreeIndex;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends SomeBaseResource implements ConfiguresTreeIndex
{
    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(Category::class)
            ->labelColumn('title')
            ->labelColumnQueryable(false)
            ->nestedSet()
            ->sortColumn('_lft')
            ->reorderable(true)
            ->inspectorPage(TreeInspectorCategory::class)
            ->modifyQuery(fn (Builder $query): Builder => static::getEloquentQuery())
            ->labels(
                treeHeading: 'Kategorien',
                treeSubheading: 'Baum',
                inspectorHeading: 'Kategorie',
                createRootLabel: 'Neue Kategorie',
                createChildLabel: 'Unterkategorie',
                newRecordLabel: 'Neue Kategorie',
            );
    }
}
```

Referenz im Projekt: `app/Filament/Resources/CategoryResource.php`.

### Schritt 2: List-Page

```php
use Heco\FilamentTreeIndex\Filament\Pages\TreeIndexListRecords;

class ListCategories extends TreeIndexListRecords
{
    protected static string $resource = CategoryResource::class;
}
```

Beim `mount()` registriert die Page die Konfiguration in der `TreeIndexConfigurationRegistry` (Schlüssel = Resource-Klassenname).

### Schritt 3: Routing

Index-Route auf die Tree-List-Page legen. Bei erweiterten Parent-Resources (z. B. Moox) die übrigen Pages des Parents beibehalten:

```php
use Illuminate\Support\Arr;

public static function getPages(): array
{
    return [
        'index' => ListCategories::route('/'),
        'tree-inspector' => TreeInspectorCategory::route('/{record}/tree-inspector'),
        ...Arr::except(parent::getPages(), ['index']),
    ];
}
```

Für schlanke eigene Resources ohne Parent-Pages reicht oft nur die Index-Route. Alternativ das Trait `Heco\FilamentTreeIndex\Filament\Concerns\ConfiguresTreeIndex` nutzen: `getTreeIndexListPage()` implementieren – setzt automatisch `'index' => …::route('/')`. Zusätzliche Pages (Inspector, Edit, …) über `getAdditionalResourcePages()` ergänzen.

### Schritt 4 (optional): Eigener Inspector

Für volle Filament-Formulare (Tabs, Relationen, Medien, …):

```php
use Heco\FilamentTreeIndex\Filament\Concerns\RendersAsTreeIndexInspector;

class TreeInspectorCategory extends EditCategory
{
    use RendersAsTreeIndexInspector;

    protected static string $resource = CategoryResource::class;
}
```

Das Trait:

- blendet die Page aus der Navigation aus,
- nutzt eine schlanke Inspector-View,
- unterdrückt Redirects nach dem Speichern (Inspector bleibt eingebettet),
- dispatcht nach dem Speichern `tree-index-record-saved`, damit der Baum links aktualisiert wird.

Der Inspector wird per `@livewire($inspectorPageClass, ['record' => $id])` in der Index-Page eingebettet. Eine eigene Route (z. B. `tree-inspector`) ist dennoch empfehlenswert, damit Filament die Edit-Page korrekt registriert (Policies, URL-Generierung).

Ohne `inspectorPage()` zeigt das Package rechts nur ein **Minimalformular** (Label + Parent-Select).

## `TreeIndexConfiguration`

| Methode | Default | Bedeutung |
|---------|---------|-----------|
| `make(Model::class)` | – | Eloquent-Model |
| `parentColumn('parent_id')` | `parent_id` | Eltern-Spalte |
| `sortColumn('sort_order')` | `sort_order` | Sortierung; bei Nested Set: `_lft` |
| `labelColumn('label')` | `label` | Anzeigetext im Baum |
| `labelColumnQueryable(false)` | `true` | `false`, wenn Label nicht per SQL selektierbar ist (z. B. Accessor); Wert wird nach `create` gesetzt |
| `nestedSet()` | `false` | Baum aus `_lft`/`_rgt` |
| `reorderable(true)` | `true` | Drag & Drop (Livewire `wire:sort`) |
| `inspectorPage(EditPage::class)` | `null` | Volles Filament-Formular rechts |
| `modifyQuery(Closure)` | – | z. B. Resource-Scopes, Soft-Deletes, Mandanten |
| `authorizationAbility('update')` | `null` | Gate-Ability für das Model; ohne Wert nur `auth()->check()` |
| `labels(...)` | deutsche Standardtexte | UI-Texte (siehe unten) |

Parameter von `labels()`: `treeHeading`, `treeSubheading`, `inspectorHeading`, `createRootLabel`, `createChildLabel`, `saveLabel`, `newRecordLabel`, `deleteConfirmMessage`.

`modifyQuery` sollte dieselbe Query-Logik wie `Resource::getEloquentQuery()` widerspiegeln (Policies, globale Scopes, Mandanten).

### Beispiel: Einfacher Baum (Adjacency List, verschiebbar)

```php
return TreeIndexConfiguration::make(TreeNode::class)
    ->parentColumn('parent_id')
    ->sortColumn('sort_order')
    ->labelColumn('label')
    ->reorderable(true);
```

### Beispiel: Nested Set

```php
return TreeIndexConfiguration::make(Category::class)
    ->labelColumn('title')
    ->labelColumnQueryable(false)
    ->nestedSet()
    ->sortColumn('_lft')
    ->reorderable(true)
    ->inspectorPage(TreeInspectorCategory::class);
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

## Actions

Geschäftslogik liegt in Action-Klassen mit `handle()`. `ResourceTreeIndex` delegiert dorthin.

| Klasse | Aufgabe |
|--------|---------|
| `CreateTreeNodeAction` | Neuer Knoten (Adjacency List); `sort_order` = max + 10; bei `nestedSet()` → `CreateNestedSetTreeNodeAction` |
| `CreateNestedSetTreeNodeAction` | Neuer Knoten per Kalnoy (`appendTo` / Kind von Parent); Model braucht `NodeTrait` |
| `UpdateTreeNodeAction` | Label/Parent aktualisieren |
| `MoveTreeNodeAction` | Parent + Geschwister-Reihenfolge; bei `nestedSet()` → `MoveNestedSetTreeNodeAction` |
| `MoveNestedSetTreeNodeAction` | Verschieben per Kalnoy (`beforeNode` / `afterNode` / `appendToNode`) |
| `DeleteTreeNodeAction` | `$record->delete()` (Kaskade über DB/FK/Model/Nested-Set) |

## Konfiguration

`config/filament-tree-index.php`:

```php
return [
    'authorization' => [
        'enabled' => true, // global abschaltbar (z. B. in Tests)
    ],
    'livewire' => [
        'alias' => 'filament-tree-index', // Livewire-Alias der ResourceTreeIndex-Komponente
    ],
];
```

Pro Resource: `authorizationAbility('update')` o. Ä. für `Gate::authorize()`. Ohne Ability reicht ein eingeloggter User.

## Tests

Alle Tests liegen im Package unter `tests/`:

| Verzeichnis | Inhalt |
|-------------|--------|
| `tests/Feature/` | Livewire `ResourceTreeIndex` (CRUD, Reorder) |
| `tests/Unit/` | `TreeIndexConfiguration`, `TreeStructure`, Registry |
| `tests/Support/` | Test-Resource, Nested-Set-Beispielkonfiguration |
| `tests/Models/` | `TreeNode` (Adjacency List), `NestedSetTreeNode` (Nested Set) |

Ausführen:

```bash
php artisan test --compact packages/filament-tree-index/tests
```

Die App-spezifische Anbindung (z. B. `CategoryResource::getPages()`) wird im Projekt separat getestet, falls nötig.

Livewire direkt testen (Authorization aus):

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

1. Model mit `parent_id` + `sort_order` **oder** Nested Set (`NodeTrait`, `_lft`/`_rgt`)
2. Bei Nested Set: `composer require kalnoy/nestedset`
3. Resource: `implements ConfiguresTreeIndex` + `treeIndex()`
4. `ListXxx extends TreeIndexListRecords`
5. `getPages()`: `'index' => ListXxx::route('/')`; bei Inspector zusätzlich Inspector-Route
6. Optional: `TreeInspectorXxx extends EditRecord` + `RendersAsTreeIndexInspector` + `inspectorPage()`
7. Package-Views im Admin-Theme `@source` eingebunden, Assets neu bauen
8. Feature-Test für `treeIndex()`-Konfiguration bzw. Livewire `ResourceTreeIndex`

## Lizenz

MIT
