# Scoped Resources (origin/source)

This document describes the current scoped origin/source system in `core`.

## Scope format

Every scope uses this shape:

`origin:source:context:boundary`

Meaning:

- `origin`: the record type that stores the scope, for example `media`, `tag`, `category`
- `source`: the structural container type, for example `draft`, `record`, `career`
- `context`: the concrete functional bucket inside that source
- `boundary`: a boundary bucket, allowed values are `private`, `public`, `group`, `user`, `user_type`

Example:

- `category:draft:draft:private`

That means:

- origin type = `category`
- source type = `draft`
- context bucket = `draft`
- boundary bucket = `private`

## Current semantics

Important rules:

- the stored `scope` is the current runtime context of the record
- a type can act as both origin and source in different situations
- `boundary` is part of the stored scope, but context matching ignores `boundary`
- boundaries are scope buckets; concrete access rules are handled by policies/services

So:

- `tag:draft:draft:private` and `tag:draft:draft:public` are different exact scopes
- but they are the same context because both share `tag:draft:draft`

Useful `Scope` helpers:

- `matchesExact()`
- `matchesContext()`
- `contextLikePattern()`
- `deriveChild()`
- `deriveChildString()`

## Query behavior

There are two match strategies:

- `exact`
- `context`

Shared helper:

- `Moox\Core\Support\Scopes\ScopeQuery`

Available methods:

- `ScopeQuery::applyExact()` filters by the full 4-part scope
- `ScopeQuery::applyContext()` filters by `origin:source:context:%`

## Global records (empty scope)

Convention:

- A record with `scope = NULL` (or empty string) is considered **global**.
- For resources that support a `scope` column, the global (unscoped) resource view shows only global records by default.

## scope_match: exact vs context

Use this to decide how a scoped list page filters records.

- `context`: filters by `origin:source:context:%` and ignores `boundary`
  - use when you want one “bundle/context” list that shows all boundaries together
  - example: `media:career:jobapplications:%` shows both `media:career:jobapplications:public` and `media:career:jobapplications:private`
- `exact`: filters by the full `origin:source:context:boundary`
  - use when you want separate list pages per boundary bucket
  - example: `media:career:jobapplications:public` shows only public media

## Boundary target conventions

To keep policy checks predictable, use a fixed context naming for target-based boundaries:

- `boundary=user`: context identifies one concrete user
  - example context: `user_123`
- `boundary=user_type`: context identifies the user model/type
  - example context: `model_app_user`, `model_moox_user`
- `boundary=group`: context identifies a group membership bucket
  - example context: `group_sales`, `group_hr`, `group_42`

Notes:

- use one naming style consistently (recommended: `snake_case`)
- keep roles/permissions separate from this; roles are handled in policies/permission system

Default rule:

- if `scope_match` is not set explicitly, the default is derived from the `scopes` table:
  - if the current `origin/source/context` has more than one active boundary, default is `exact`
  - if only one boundary is active, default is `context`

Current resource behavior:

- scoped origin resources default to `scope_match = context`
- scoped resources can explicitly use `exact` or `context`
- new child records still store the full 4-part derived scope
- global resources are currently unscoped unless a separate rule is added

Practical consequence:

- `Draft > Tags` shows both `tag:draft:draft:private` and `tag:draft:draft:public`
- the global `Tags` resource currently still shows all tags
- policy handling for `private/public/group/user/user_type` is a separate layer

## Scope config

Sources define scoped origins declaratively in config:

```php
'resources' => [
    'record' => [
        'single' => 'trans//record::record.record',
        'plural' => 'trans//record::record.records',
        'scopes' => [
            'category' => [
                'enabled' => true,
                'resource' => \Moox\Category\Moox\Entities\Categories\Category\CategoryResource::class,
            ],
        ],
    ],
],
```

Definition rules:

- `enabled => false` disables an origin scope without removing config
- `resource` is the only required entry in the normal case
- `origin` defaults to the config key and usually does not need to be written
- `slug` defaults to `<source-slug>/<origin-key>`
- source scope is derived automatically from the source key
- default parent derivation is `<key>:<key>:<key>:private`
- `scope` can override the derived base scope if really needed
- `context` can override the third segment
- `boundary` can override the fourth segment
- `source` can override the inherited source
- `scope_match` can explicitly choose `exact` or `context`

## Scope derivation

Default derivation keeps source, context and boundary and only swaps the origin.

Example:

- derived source scope for key `draft`: `draft:draft:draft:private`
- origin `tag` becomes: `tag:draft:draft:private`

Optional overrides:

```php
$childScope = $record->deriveChildScope(
    'tag',
    context: 'career',
    boundary: 'public',
);
```

This derives:

- `tag:draft:career:public`

Available derivation APIs:

- `Scope::deriveChild()`
- `Scope::deriveChildString()`
- `HasScopedModel::deriveChildScope()`
- `HasScopedModel::deriveScopeForOrigin()` as compatibility alias

## Sync config scopes to DB

The `scopes` table can be bootstrapped from package config definitions:

```bash
php artisan scopes:sync
```

Useful flags:

- `--dry-run`: prints rows that would be upserted, writes nothing
- `--disable-missing`: sets `is_active=false` for DB scopes not present in current config

This command remains governance/bootstrap. Runtime filtering still uses origin table scopes via `ScopeQuery`, but now only rows with an active matching entry in `scopes` are considered.
Additionally, scoped child navigation items are hidden when their configured scope exists in `scopes` and is inactive.

## Creating a source (container)

If a type should only act as a source container:

1. Make sure the source plugin uses `ChildResourceRegistrar::registerFromParentDefinition(...)`.
2. Add the origin scopes under `scopes` in the source config.
3. Do not add a `scope` column to the source table unless source records themselves must persist a scope.

Important:

- the source key alone is enough for deriving origin scopes like `category:record:record:private`
- a source does not need `HasScopedModel` just because it has scopes
- a source only needs its own `scope` column if source records themselves should store a scope
- in the common case, a scope config only needs `resource` and optionally `enabled`

Minimal source example:

```php
class RecordPlugin implements Plugin
{
    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            RecordResource::class,
            'record',
            config('record.resources.record', []),
        );
    }
}
```

## Creating an origin (scoped model)

If a type should be reused under one or more targets as a real scoped origin:

1. Add a nullable indexed `scope` column to the origin table.
2. Add `HasScopedModel` to the origin model.
3. Make the origin resource scope-aware.
4. Register the origin under one or more targets in config.
5. Scope internal self-selectors too, if the origin can point to its own records.

Minimal origin model:

```php
class ExampleChild extends Model
{
    use \Moox\Core\Models\Concerns\HasScopedModel;
}
```

Origin resource (single supported way):

- `HasScopedChildResource`
- `scopeQuery()`
- `applyScopedDefaults()`
- `resolveScopedNavigationLabel()`
- `resolveScopedNavigationGroup()`
- `resolveScopedNavigationRegistration()`
- `resolveScopedNavigationParentItem()`
- `resolveScopedNavigationSort()`

## Scope-ready origin model

Real origin records need a `scope` column on the origin table.

Recommended setup:

- add a nullable indexed `scope` column in a migration
- use `Moox\Core\Models\Concerns\HasScopedModel`
- optionally implement `getDefaultScope()` if the origin model itself should default a stored scope

The trait provides:

- merged `scope` fillable support
- merged `ScopeCast`
- optional default scope on create
- current scope accessors
- scope derivation helpers

## Moving records between scopes (no pivot)

Current model is intentionally simple:

- each scopable record stores exactly one `scope` string
- moving between scopes means updating this `scope` value
- if no scope is assigned, records fall back to a global scope (`origin:global:global:private`)

UI assignment:

- scoped resources using `HasScopedChildResource` can use `getAssignScopeBulkAction()`
- this provides a bulk action with active scopes + `Global`
- selecting `Global` sets the record back to the global fallback scope
- write validation is applied on assign: target scope must be active and boundary rules must be fulfilled

## Reuse examples

- `category` under `draft` stores scopes like `category:draft:draft:private`
- `category` under `record` stores scopes like `category:record:record:private`
- `media` under that same context can derive `media:draft:draft:private`

The key point is:

- the origin record stores the scope
- the source container does not automatically need to store one

---

# Scoped Resources (Origin/Source) — Deutsch

Dieses Dokument beschreibt das aktuelle Scope-System (Origin/Source) in `core`.

## Kochrezepte (Schritt für Schritt)

### Rezept A: Source (Container) erstellen

Ziel: Eine Resource soll “Container” sein (z.B. `record`, `draft`, später `career`) und darunter sollen scoped Origins hängen (z.B. `media`, `tag`, `category`).

Schritte:

1. **Source-Config anlegen/öffnen** (z.B. `packages/<source>/config/<source>.php`).
2. **In `resources.<sourceKey>.scopes` die Origins definieren**.
3. **Plugin registriert den Source-Container via Registrar**:
   - im Plugin `register()` muss `ChildResourceRegistrar::registerFromParentDefinition(...)` aufgerufen werden.
4. **(Optional) Source-Slug setzen**:
   - `resources.<sourceKey>.slug` (Standard ist `<sourceKey>`).
5. **Fertig**: In Filament erscheint der Source-Container und darunter die Origin-Scopes als eigene List-Page.

Minimalbeispiel Config:

```php
'resources' => [
    'record' => [
        'single' => 'Record',
        'plural' => 'Records',
        'scopes' => [
            'media' => [
                'enabled' => true,
                'resource' => \Moox\Media\Resources\MediaResource::class,
            ],
        ],
    ],
],
```

Minimalbeispiel Plugin:

```php
ChildResourceRegistrar::registerFromParentDefinition(
    $panel,
    \Moox\Record\Moox\Entities\Records\Record\RecordResource::class,
    'record',
    config('record.resources.record', []),
);
```

### Rezept B: Origin (scoped “Child”) erstellen

Ziel: Ein Eloquent Model soll “scope-aware” sein und unter einem oder mehreren Sources wiederverwendbar sein.

Schritte:

1. **DB: `scope` Spalte hinzufügen**
   - Migration: `scope` als `string`/nullable + index auf der Origin-Tabelle (z.B. `media`, `tags`, `categories`).
2. **Model: `HasScopedModel` aktivieren**
   - im Origin-Model `use \Moox\Core\Models\Concerns\HasScopedModel;`
3. **Resource: scope-aware machen**
   - `HasScopedChildResource` nutzen (einziger dokumentierter Weg).
4. **Origin unter einem Target einhängen**
   - im Target-Config unter `scopes` eintragen:
     - Key = Origin-Key (z.B. `media`)
     - `resource` = Filament Resource-Klasse
     - optional `enabled`
5. **Upload/Erstellung prüfen**
   - in der scoped Origin-Ansicht (z.B. `Draft > Media`) einen Datensatz erstellen
   - prüfen, dass `scope` gesetzt ist (entweder aus Config `scope` oder aus Ableitung).

Minimalbeispiel Model:

```php
class Media extends Model
{
    use \Moox\Core\Models\Concerns\HasScopedModel;
}
```

Minimalbeispiel Target-Eintrag:

```php
'scopes' => [
    'media' => [
        'enabled' => true,
        'resource' => \Moox\Media\Resources\MediaResource::class,
    ],
],
```

## Scope-Format

Jeder Scope hat diese Form:

`origin:source:context:boundary`

Bedeutung:

- `origin`: der Record-Typ, der den Scope speichert (z. B. `media`, `tag`, `category`)
- `source`: der strukturelle Container-Typ (z. B. `draft`, `record`, `career`)
- `context`: der konkrete fachliche Bucket innerhalb des Targets
- `boundary`: ein Boundary-Bucket; erlaubte Werte sind `private`, `public`, `group`, `user`, `user_type`

Beispiel:

- `category:draft:draft:private`

Das heißt:

- Origin-Typ = `category`
- Source-Typ = `draft`
- Context-Bucket = `draft`
- Boundary-Bucket = `private`

## Aktuelle Semantik

Wichtige Regeln:

- der gespeicherte `scope` ist der aktuelle Laufzeit-Kontext des Records
- ein Typ kann in unterschiedlichen Situationen sowohl Origin als auch Target sein
- `boundary` ist Teil des gespeicherten Scopes, aber Context-Matching ignoriert `boundary`
- Boundaries sind Scope-Buckets; konkrete Zugriffsregeln liegen in Policies/Services

Das bedeutet:

- `tag:draft:draft:private` und `tag:draft:draft:public` sind unterschiedliche exakte Scopes
- aber derselbe Context, weil beide `tag:draft:draft` teilen

Hilfreiche `Scope`-Helper:

- `matchesExact()`
- `matchesContext()`
- `contextLikePattern()`
- `deriveChild()`
- `deriveChildString()`

## Query-Verhalten

Es gibt zwei Match-Strategien:

- `exact`
- `context`

Shared Helper:

- `Moox\Core\Support\Scopes\ScopeQuery`

Verfügbare Methoden:

- `ScopeQuery::applyExact()` filtert auf den vollen 4-Teil-Scope
- `ScopeQuery::applyContext()` filtert auf `origin:source:context:%`

## scope_match: exact vs context

Damit legst du fest, wie eine scoped List-Page Records filtert.

- `context`: filtert auf `origin:source:context:%` und ignoriert `boundary`
  - sinnvoll, wenn du eine „Bundle/Context“-Liste willst, die alle Boundaries zusammen zeigt
  - Beispiel: `media:career:jobapplications:%` zeigt sowohl `...:public` als auch `...:private`
- `exact`: filtert auf den kompletten `origin:source:context:boundary`
  - sinnvoll, wenn du getrennte Listen pro Boundary-Bucket willst
  - Beispiel: `media:career:jobapplications:public` zeigt nur Public-Media

## Boundary-Target-Konventionen

Damit Policy-Checks später eindeutig bleiben, nutze feste Context-Namen für zielbasierte Boundaries:

- `boundary=user`: Context identifiziert genau einen User
  - Beispiel-Context: `user_123`
- `boundary=user_type`: Context identifiziert den User-Model-/Typ
  - Beispiel-Context: `model_app_user`, `model_moox_user`
- `boundary=group`: Context identifiziert eine Gruppen-Mitgliedschaft
  - Beispiel-Context: `group_sales`, `group_hr`, `group_42`

Hinweise:

- nutze durchgehend ein Namensschema (empfohlen: `snake_case`)
- Rollen/Permissions getrennt halten; das läuft über Policies/Permission-System

Standardregel:

- wenn `scope_match` nicht explizit gesetzt ist, wird der Default aus der `scopes`-Tabelle abgeleitet:
  - wenn für `origin/source/context` mehr als eine Boundary aktiv ist, ist der Default `exact`
  - wenn nur eine Boundary aktiv ist, ist der Default `context`

Aktuelles Resource-Verhalten:

- scoped Origin-Resources nutzen standardmäßig `scope_match = context`
- scoped Resources können explizit `exact` oder `context` verwenden
- neue Origin-Records speichern weiterhin den vollen 4-Teil-Scope
- globale Resources sind aktuell unscoped (bis eine separate Regel hinzugefügt wird)

Praktische Konsequenz:

- `Draft > Tags` zeigt sowohl `tag:draft:draft:private` als auch `tag:draft:draft:public`
- die globale `Tags`-Resource zeigt aktuell weiterhin alle Tags
- Permissions/Visibility für `private/public/group/user/user_type` ist bewusst ein separater Layer

## Scope-Config

Sources definieren scoped Origins deklarativ in der Config:

```php
'resources' => [
    'record' => [
        'single' => 'trans//record::record.record',
        'plural' => 'trans//record::record.records',
        'scopes' => [
            'category' => [
                'enabled' => true,
                'resource' => \Moox\Category\Moox\Entities\Categories\Category\CategoryResource::class,
            ],
        ],
    ],
],
```

Regeln:

- `enabled => false` deaktiviert einen Origin-Scope, ohne die Config zu löschen
- `resource` ist im Normalfall der einzige notwendige Eintrag
- `origin` ergibt sich aus dem Config-Key und muss meist nicht explizit gesetzt werden
- `slug` ist standardmäßig `<source-slug>/<origin-key>`
- der Source-Scope wird automatisch aus dem Source-Key abgeleitet
- Default-Ableitung ist `<key>:<key>:<key>:private`
- `scope` kann den abgeleiteten Base-Scope überschreiben (nur wenn wirklich nötig)
- `context` kann das dritte Segment überschreiben
- `boundary` kann das vierte Segment überschreiben
- `source` kann das vererbte Source überschreiben
- `scope_match` kann explizit `exact` oder `context` setzen

## Scope-Ableitung

Die Default-Ableitung behält `source`, `context` und `boundary` und tauscht nur `origin` aus.

Beispiel:

- abgeleiteter Source-Scope für Key `draft`: `draft:draft:draft:private`
- Origin `tag` wird: `tag:draft:draft:private`

Optionale Overrides:

```php
$childScope = $record->deriveChildScope(
    'tag',
    context: 'career',
    boundary: 'public',
);
```

Das ergibt:

- `tag:draft:career:public`

Verfügbare Ableitungs-APIs:

- `Scope::deriveChild()`
- `Scope::deriveChildString()`
- `HasScopedModel::deriveChildScope()`
- `HasScopedModel::deriveScopeForOrigin()` als Kompatibilitäts-Alias

## Config-Scopes in DB synchronisieren

Die `scopes`-Tabelle kann aus den Package-Config-Definitionen aufgebaut werden:

```bash
php artisan scopes:sync
```

Nützliche Flags:

- `--dry-run`: zeigt nur an, was upserted würde (kein Schreiben)
- `--disable-missing`: setzt `is_active=false` für DB-Scopes, die aktuell nicht in der Config vorkommen

Der Command bleibt für Bootstrap/Governance. Das Runtime-Filtering läuft weiterhin über Origin-Scopes + `ScopeQuery`, berücksichtigt jetzt aber nur noch Scopes mit aktivem Eintrag in `scopes`.
Zusätzlich werden scoped Child-Navigationseinträge ausgeblendet, wenn ihr konfigurierter Scope in `scopes` vorhanden und inaktiv ist.

## Source erstellen (Container)

Wenn ein Typ nur als Source-Container dienen soll:

1. Stelle sicher, dass das Source-Plugin `ChildResourceRegistrar::registerFromParentDefinition(...)` nutzt.
2. Lege die Origins unter `scopes` in der Source-Config an.
3. Füge der Source-Tabelle keine `scope`-Spalte hinzu, außer wenn Source-Records selbst einen Scope speichern müssen.

Wichtig:

- der Source-Key allein reicht, um Origin-Scopes wie `category:record:record:private` abzuleiten
- ein Source braucht nicht `HasScopedModel`, nur weil es `scopes` hat
- ein Source braucht nur dann eine eigene `scope`-Spalte, wenn Source-Records selbst Scopes speichern sollen
- im Normalfall braucht ein Scope-Eintrag nur `resource` und optional `enabled`

Minimales Target-Beispiel:

```php
class RecordPlugin implements Plugin
{
    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            RecordResource::class,
            'record',
            config('record.resources.record', []),
        );
    }
}
```

## Origin erstellen (scoped Model)

Wenn ein Typ unter einem oder mehreren Targets als echter scoped Origin wiederverwendet werden soll:

1. Füge eine nullable, indexierte `scope`-Spalte zur Origin-Tabelle hinzu.
2. Füge `HasScopedModel` zum Origin-Model hinzu.
3. Mache die Origin-Resource scope-aware.
4. Registriere den Origin unter einem oder mehreren Targets in der Config.
5. Scope interne Self-Selector-Queries ebenfalls, falls der Origin auf eigene Records referenzieren kann.

Minimales Origin-Model:

```php
class ExampleChild extends Model
{
    use \Moox\Core\Models\Concerns\HasScopedModel;
}
```

Bevorzugte Origin-Resource:

```php
```

Resource Setup (einziger dokumentierter Weg):

- `HasScopedChildResource`
- `scopeQuery()`
- `applyScopedDefaults()`
- `resolveScopedNavigationLabel()`
- `resolveScopedNavigationGroup()`
- `resolveScopedNavigationRegistration()`
- `resolveScopedNavigationParentItem()`
- `resolveScopedNavigationSort()`

## Scope-ready Origin-Model

Echte Origin-Records brauchen eine `scope`-Spalte in der Origin-Tabelle.

Empfohlene Einrichtung:

- füge eine nullable, indexierte `scope`-Spalte per Migration hinzu
- nutze `Moox\Core\Models\Concerns\HasScopedModel`
- optional implementiere `getDefaultScope()`, wenn das Origin-Model selbst einen Default-Scope setzen soll

Das Trait liefert:

- gemergte `scope` fillable Unterstützung
- gemergtes `ScopeCast`
- optionalen Default-Scope beim Create
- Zugriff auf den aktuellen Scope
- Helper zur Scope-Ableitung

## Records zwischen Scopes verschieben (ohne Pivot)

Das aktuelle Modell ist bewusst einfach:

- jeder scopable Record hat genau einen `scope`-String
- Verschieben zwischen Scopes bedeutet: diesen `scope`-Wert aktualisieren
- ohne explizite Zuweisung greift ein globaler Fallback-Scope (`origin:global:global:private`)

UI-Zuweisung:

- scoped Resources mit `HasScopedChildResource` können `getAssignScopeBulkAction()` verwenden
- die Bulk-Action bietet aktive Scopes + `Global`
- `Global` setzt den Record zurück auf den globalen Fallback-Scope
- beim Zuweisen greift Write-Validation: Ziel-Scope muss aktiv sein und Boundary-Regeln müssen erfüllt sein
- im Dropdown wird zur eindeutigen Auswahl angezeigt: `label — source/context (boundary)` (Fallback: Scope-String)

## Aktueller Stand (Kurzüberblick)

### 1) Navigation ist fail-closed

- Scoped Child-Navigation wird nur angezeigt, wenn der Scope in `scopes` existiert und `is_active=true` ist.
- Scope nicht in `scopes` oder `is_active=false` => Navigation wird nicht registriert.

### 2) Mehrere Scopes für dieselbe Resource unter einem Source

- Eine Resource (z. B. `MediaResource`) kann mehrfach unter einem Source eingehängt werden.
- Dafür verschiedene Config-Keys verwenden (z. B. `media`, `media_public`).
- Damit es in der UI eindeutig ist, pro Eintrag `label`/`navigation_label` setzen.
- Wenn `label` fehlt, nutzen beide Einträge das Resource-Standardlabel und sehen gleich aus.

Beispiel:

```php
'scopes' => [
    'media' => [
        'resource' => \Moox\Media\Resources\MediaResource::class,
        'origin' => 'media',
        'boundary' => 'private',
        'label' => 'Media Private',
    ],
    'media_public' => [
        'resource' => \Moox\Media\Resources\MediaResource::class,
        'origin' => 'media',
        'boundary' => 'public',
        'label' => 'Media Public',
    ],
],
```

### 3) Sync nach Config-Änderungen

- Nach Änderungen in `resources.*.scopes` immer `php artisan scopes:sync` ausführen.
- Erst danach sind neue/angepasste Scopes in `scopes` verfügbar.
- Navigation/Filter arbeiten auf dieser Basis.

## Reuse-Beispiele

- `category` unter `draft` speichert Scopes wie `category:draft:draft:private`
- `category` unter `record` speichert Scopes wie `category:record:record:private`
- `media` kann im selben Context z. B. `media:draft:draft:private` ableiten

Kernaussage:

- der Origin-Record speichert den Scope
- der Target-Container muss nicht automatisch einen eigenen Scope speichern
