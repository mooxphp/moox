# Entscheidungsmatrix — moox/tree Integration

Für jede Gabelung: `AskQuestion`, wenn der Kontext die Wahl nicht eindeutig macht.

---

## 1. Baum-Modus

**Frage:** Welcher Baum-Modus passt zum Model?

| Option | Wann wählen | Model-Anforderungen | `treeIndex()`-Zusatz |
|--------|-------------|---------------------|----------------------|
| **Adjacency List** | Einfache Hierarchie, `parent_id` + `sort_order` in DB | `parent_id` (nullable FK), `sort_order`, Label-Spalte | Standard-`make()`; ggf. `parentColumn()`, `sortColumn()`, `labelColumn()` |
| **Nested Set** | Viele Reads, häufiges Verschieben, Kalnoy bereits im Einsatz | `_lft`, `_rgt`, `NodeTrait`; `composer require kalnoy/nestedset` | `->nestedSet()->sortColumn('_lft')->reorderable(true)` |

**Konsequenzen:**

- Adjacency: `CreateTreeNodeAction` / `MoveTreeNodeAction`
- Nested Set: `*NestedSet*` Actions; **nicht** Adjacency- und Nested-Logik mischen
- Label aus Accessor/Translation: `->labelColumn('title')->labelColumnQueryable(false)`

**Referenz Nested Set:** `CategoryTreeResource::treeIndex()`

---

## 2. Resource-Muster

**Frage:** Separate Tree-Resource oder eine Resource mit Trait?

| Option | Wann wählen | Dateien | Routing |
|--------|-------------|---------|---------|
| **Muster A: Separate `XxxTreeResource`** | Bestehende Tabellen-Resource bleibt; zweite Navigation für Baum-UI (Moox-Standard) | `XxxTreeResource`, `TreeListXxx`, optional `TreeInspectorXxx`, `XxxTreePlugin` | `getPages()` mit `Arr::except(parent::getPages(), ['index'])` |
| **Muster B: Eine Resource + Trait** | Neues Modul nur mit Baum, keine parallele Listen-Resource | Resource mit `use ConfiguresTreeIndex` | Trait setzt `index`-Route; `getAdditionalResourcePages()` für Inspector |

**Konsequenzen Muster A:**

- `XxxTreeResource extends XxxResource implements ConfiguresTreeIndex`
- Tabellen-Resource unverändert in bestehendem Plugin
- Eigenes `XxxTreePlugin` registriert nur `XxxTreeResource`

**Konsequenzen Muster B:**

- `protected static function getTreeIndexListPage(): string`
- `protected static function getAdditionalResourcePages(): array` für `tree-inspector`

**Referenz Muster A:** `packages/category` — `CategoryTreeResource`, `CategoryTreePlugin`

---

## 3. Listen-Anbindung

**Frage:** Wie wird die **Basis-Query** angebunden? (Toolbar-Details → §6)

| Option | Wann wählen | Config |
|--------|-------------|--------|
| **`forwardFromResource(...)`** | Bestehende Resource mit `table()` / Policies / `getEloquentQuery()` | Query + optional Filament- oder Baum-Toolbar (§6) |
| **Manuell** | Keine sinnvolle Basis-Resource / nur `modifyQuery` | `modifyQuery(fn => static::getEloquentQuery())` |

**Konsequenzen `forwardFromResource`:**

- `modifyQuery` → `Resource::getEloquentQuery()`; mit Filament-Toolbar zusätzlich Filter der List-Page
- List-Page mit Tabs: bei Tab-Wechsel `refreshTreeIndexConfiguration()` aufrufen

**Ohne `forwardFromResource`:** `modifyQuery` muss dieselbe Sicht wie `getEloquentQuery()` abbilden (Policies, Scopes, Mandanten).

---

## 6. Toolbar: Suche, Filter, Sprach-Switcher

**Pflicht vor Implementierung:** Wenn der User nicht explizit sagt, was er braucht → **`AskQuestion` mit drei Questions** (eine Runde). Erst danach `treeIndex()` und Resource-Dateien anlegen.

### AskQuestion-Vorlage

| # | Question (Prompt) | Optionen | `id` (Beispiel) |
|---|-------------------|----------|-----------------|
| 1 | Soll die Tree-Ansicht eine **Suche** haben? | Ja / Nein | `toolbar_search` |
| 2 | Sollen **Filter oder Tabs** aus der Listen-Resource übernommen werden? | Ja / Nein | `toolbar_filters` |
| 3 | Braucht die Tree-Ansicht einen **Sprach-Switcher**? | Ja / Nein | `toolbar_language` |

**Nicht fragen, wenn Kontext eindeutig ist:**

| Situation | Annahme |
|-----------|---------|
| User: „ohne Sprache / kein Language Switcher“ | Sprache = Nein |
| User: „Suche funktioniert nicht“ / „mit Suche wie Liste“ | Suche = Ja |
| Model mit `translations` / Category-Pattern | Sprache = Ja |
| Einfache Hierarchie ohne i18n (z. B. User, Org-Chart) | Sprache = Nein |
| Bestehende Resource mit `table()->filters()` / Tabs | Filter = Ja, wenn User nicht widerspricht |

### Entscheidungsmatrix → Config

| Suche | Filter | Sprache | `treeIndex()`-Pfad | Zusatz in Resource/List-Page |
|:-----:|:------:|:-------:|--------------------|--------------------------------|
| Ja | Ja | Ja | `forwardFromResource(static::class, useFilamentTableToolbar: true)` | `getTitleColumn()` **oder** `applyListSearchToQuery()`; bei Translation `labelColumnQueryable(false)` |
| Ja | Ja | Nein | wie oben + `->filamentTableLanguageSwitcher(false)` | wie oben |
| Ja | Nein | Nein | `forwardFromResource(static::class, useFilamentTableToolbar: false)` + `->toolbarSearch()` | `applyListSearchToQuery()` oder `applySearchUsing()` |
| Nein | Ja | Nein | `forwardFromResource(static::class, useFilamentTableToolbar: true)` + `->filamentTableLanguageSwitcher(false)` | Suchfeld in `table()` ggf. deaktivieren (searchable `false`), sonst leeres Suchfeld sichtbar |
| Nein | Nein | Ja | `forwardFromResource(static::class, useFilamentTableToolbar: false)` + `->toolbarLanguageSwitcher()` | `applyLanguageUsing()` bei Bedarf |
| Nein | Nein | Nein | **Kein** `forwardFromResource` — nur `->modifyQuery(fn (Builder $q) => static::getEloquentQuery())` | Keine Toolbar-Extras |

### Suche — technische Pflichten

Ohne funktionierende Suche bricht der Forwarder still ab, wenn weder `getTitleColumn()` noch `applyListSearchToQuery()` existiert.

```php
// Auf XxxTreeResource oder XxxResource (wenn forwardFromResource darauf zeigt):
public static function applyListSearchToQuery(Builder $query, string $search): Builder
{
    $search = trim($search);
    if ($search === '') {
        return $query;
    }
    return $query->where('name', 'like', "%{$search}%"); // Domäne anpassen
}
```

Referenz: `UserTreeResource::applyListSearchToQuery()`.

### Filter / Tabs

- **Filter Ja:** `useFilamentTableToolbar: true` + List-Page `extends TreeIndexListRecords`; Filter aus `Resource::table()`.
- **Tabs Ja:** `HasListPageTabs` auf `TreeListXxx`; `updatedActiveTab()` → `refreshTreeIndexConfiguration()`; ggf. `getTableQuery()` in `applyForwardedListQuery()` (siehe `TreeListUsers`).

### Sprach-Switcher

| Toolbar-Modus | Sprache Ja | Sprache Nein |
|---------------|------------|--------------|
| Filament-Table-Toolbar (`useFilamentTableToolbar: true`) | Standard (nichts extra) | `->filamentTableLanguageSwitcher(false)` |
| Baum-Spalte (`useFilamentTableToolbar: false`) | `->toolbarLanguageSwitcher()` + ggf. `toolbarLocalizedTranslations()` | weglassen |

**Referenz ohne Sprache:** `UserTreeResource::treeIndex()`  
**Referenz mit Sprache:** `CategoryTreeResource::treeIndex()`

---

## 4. Inspector

**Frage:** Volles Filament-Formular oder Minimalformular?

| Option | Wann wählen | Dateien | Config |
|--------|-------------|---------|--------|
| **Volles Formular** | Edit-Page mit Tabs, Relationen, Medien existiert oder geplant | `TreeInspectorXxx extends EditXxx` + `RendersAsTreeIndexInspector`; Route `tree-inspector` | `->inspectorPage(TreeInspectorXxx::class)` |
| **Minimalformular** | Nur Label + Parent reichen | Keine Inspector-Page nötig | `inspectorPage()` weglassen |

**Konsequenzen volles Formular:**

- `$resource` in Inspector = **Basis-Resource** (`XxxResource`), nicht `XxxTreeResource`
- Route `tree-inspector` empfohlen (Policies, URLs)
- Trait: kein Redirect nach Save, Event `tree-index-record-saved`

**Referenz:** `TreeInspectorCategory` → `$resource = CategoryResource::class`

---

## 5. Fehlende Fähigkeit

**Frage:** Verhalten nicht über bestehende Config erreichbar — Closure im Consumer oder Package erweitern?

| Option | Wann wählen | Aktion |
|--------|-------------|--------|
| **Config-Closures im Consumer** | Nur diese Resource/Domäne betroffen | `modifyQuery`, `applySearchUsing`, `applyLanguageUsing`, `labels()` |
| **Feature in `packages/tree`** | Andere Bäume würden dasselbe brauchen | **Nicht** im Consumer duplizieren; User bitten, Package-Regel zu nutzen und Skill zu verlassen |

**Regel:** Generische UI/CRUD (Toolbar, Reorder, Expand, Validierung) gehört ins Package. Domänen-Inhalte (Formular-Felder, Relationen) nur im Inspector.

---

## Schnell-Matrix (häufigster Moox-Pfad)

**Vor dem Coden:** §6 abfragen (Suche / Filter / Sprache).

```
Nested Set + Muster A + Suche+Filter+Sprache Ja + Inspector
```

```php
TreeIndexConfiguration::make(Model::class)
    ->forwardFromResource(static::class, useFilamentTableToolbar: true)
    ->labelColumn('title')
    ->labelColumnQueryable(false)
    ->nestedSet()
    ->sortColumn('_lft')
    ->reorderable(true)
    ->inspectorPage(TreeInspectorXxx::class)
    ->labels(...);
```

**Häufig ohne Sprache** (z. B. User, Menü ohne i18n):

```php
TreeIndexConfiguration::make(Model::class)
    ->forwardFromResource(static::class, useFilamentTableToolbar: true)
    ->filamentTableLanguageSwitcher(false)
    // + applyListSearchToQuery() wenn keine getTitleColumn()
    ->parentColumn('parent_id')
    ->sortColumn('sort_order')
    ->labelColumn('name')
    ->reorderable(true)
    ->inspectorPage(TreeInspectorXxx::class);
```

Adjacency-List-Variante: `nestedSet()` und `sortColumn('_lft')` weglassen; ggf. `parentColumn` / `sortColumn` / `labelColumn` setzen.
