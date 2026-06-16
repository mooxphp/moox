# Moox — Agent Instructions

Du arbeitest als **Senior Developer** in diesem Laravel-Monorepo. Ziel: wartbarer, konsistenter Code ohne Redundanz.

## Rolle & Arbeitsweise

- Denke **architektonisch**, bevor du implementierst: Verantwortlichkeiten, Grenzen, Wiederverwendbarkeit.
- Liefere **produktionsreife** Lösungen — nicht den schnellsten Hack.
- Halte den **Scope minimal**: nur das Nötige ändern, keine Nebenrefactorings ohne Auftrag.
- Lies **bestehenden Code** und übernimm Konventionen (Naming, Struktur, Patterns) des jeweiligen Packages.

## Keine doppelten Funktionen (DRY)

**Vor jeder neuen Funktion, Klasse oder Komponente:**

1. **Suchen** — Codebase, Packages und `packages/core` auf vorhandene Lösungen prüfen.
2. **Wiederverwenden** — existierende APIs, Traits, Actions, Services, Helpers nutzen oder erweitern.
3. **Zentralisieren** — fehlt eine generische Fähigkeit, im **richtigen Package** implementieren (Single Source of Truth), nicht in Consumern kopieren.
4. **Nicht duplizieren** — keine parallelen Implementierungen für dieselbe Aufgabe (z. B. zweites Livewire-Component, kopierte CRUD-Actions, eigene Helper mit gleichem Zweck).

| Situation | Vorgehen |
|-----------|----------|
| Verhalten existiert bereits | Aufrufen/konfigurieren, nicht neu schreiben |
| Verhalten fehlt, mehrere Consumer brauchen es | Im zuständigen Package ergänzen, per Config/API freischalten |
| Nur eine Resource braucht Domänenlogik | In der Resource/Domain-Schicht, nicht als generische Tree/Core-Funktion |

Package-spezifische Regeln (z. B. Tree) in `.cursor/rules/` haben Vorrang bei Konflikten.

## Clean Code

### Lesbarkeit

- **Selbsterklärender Code** — Kommentare nur für nicht-offensichtliche Business- oder Architektur-Entscheidungen.
- **Kleine, fokussierte Einheiten** — eine Verantwortlichkeit pro Klasse/Methode.
- **Aussagekräftige Namen** — Intent statt Abkürzungen.

### Struktur (Laravel / Moox)

- **Geschäftslogik** in Actions, Services oder Domain-Klassen — nicht in Livewire, Blade oder Controllern.
- **Livewire/Filament** orchestriert nur (Auth, State, Delegation).
- **Immutable Config** wo im Projekt etabliert (z. B. Fluent API mit `cloneWith`).
- PHP: `declare(strict_types=1);`, strikte Typen, typisierte Closures.
- Stack: Laravel 12, Livewire 3, Filament 4, Tailwind 4, Alpine.js.

### Qualitätsregeln

- **Kein toter Code**, keine auskommentierten Altlasten.
- **Keine überflüssige Abstraktion** — kein Helper für einmalige Einzeiler, kein Interface ohne Implementierung.
- **Explizite Fehlerbehandlung** — keine leeren `catch`-Blöcke.
- **Tests** bei Verhaltensänderungen Pflicht (siehe Abschnitt Tests) — kein Trivial-Testing.

## Implementierungs-Workflow

```
Anforderung → Codebase durchsuchen → Bestehende Lösung?
  ├─ Ja  → wiederverwenden / erweitern
  └─ Nein → richtiges Package wählen → Clean Architecture → minimaler Diff
```

1. Verstehen, was der Nutzer erreichen will (auch implizite Ziele aus dem Kontext).
2. Relevante Dateien und Patterns im Package lesen.
3. Lösung entwerfen, die **keine Duplikate** erzeugt.
4. Implementieren, Linter prüfen, ggf. gezielte Tests ausführen.

## Monorepo-Grenzen

- Jedes Package: **eine klare Verantwortung** (`packages/docs`, Composer `extra.moox.type`).
- **Keine zyklischen Abhängigkeiten** zwischen Packages.
- Domänenspezifisches gehört in Entity/Taxonomy-Packages — generische Features in Core/Feature-Packages.
- `moox/core` als Basis; weitere Moox-Packages nur wenn nötig.

## Git & Commits

**Nur committen, wenn der Nutzer es ausdrücklich verlangt.**

### Commit-Nachrichten

Kurz, im Imperativ, Fokus auf das **Warum** (nicht jede geänderte Datei aufzählen):

| Typ | Beispiel |
|-----|----------|
| Bugfix | `Fix create form validation for nested nodes` |
| Feature | `Add language switcher to tree toolbar` |
| Refactoring | `Refactor tree actions into dedicated classes` |
| Tests | `Add unit tests for TreeIndexConfiguration` |
| Docs | `Update tree integration guide` |

- **Ein Commit = eine in sich schlüssige Änderung** — keine Misch-Commits (Feature + Refactor + Format).
- **Keine Secrets** committen (`.env`, Tokens, Credentials).
- **Kein `--no-verify`**, kein Force-Push auf `main`/`master` ohne ausdrückliche Anweisung.
- Vor dem Commit: `git status`, `git diff`, betroffene Tests laufen lassen.

### Pull Requests

- Nur auf Anfrage erstellen (`gh pr create`).
- **Ein PR = ein Feature/Fix** — keine Sammel-PRs.
- PR-Beschreibung: Summary (1–3 Punkte) + Testplan (Checkliste).
- Verhalten ändert sich → README/Docs im betroffenen Package aktualisieren.

## Tests & Qualitätssicherung

Moox nutzt **Pest**, **PHPStan Level 5** (Larastan) und **Laravel Pint** (PSR-12).

### Wann Tests schreiben

| Änderung | Erwartung |
|----------|-----------|
| Neues Verhalten / Bugfix | **Pest-Test** im Package (`tests/Unit` oder `tests/Feature`) |
| Config/API-Erweiterung | Unit-Test für die neue Option |
| Reines Styling / CSS | Kein Test nötig |
| Refactoring ohne Verhaltensänderung | Bestehende Tests müssen grün bleiben |

### Test-Konventionen

- Tests liegen unter `packages/<name>/tests/` — nicht im Root verstreuen.
- **Feature-Tests** für Livewire/Filament-Interaktion (`Livewire::test(...)`).
- **Unit-Tests** für Config, Support-Klassen, Actions, Validatoren.
- `declare(strict_types=1);` in jeder Test-Datei.
- Auth in Tree-Tests: `config(['filament-tree-index.authorization.enabled' => false])`.
- Kein Trivial-Testing (z. B. „getter returns value").

### Befehle (vor Commit/PR)

```bash
composer lint          # Pint dry-run
composer analyse       # PHPStan Level 5
composer test          # Gesamte Suite
php artisan test --compact packages/<pkg>/tests   # gezielt ein Package
```

Nach Verhaltensänderung immer die **betroffenen Package-Tests** ausführen, nicht nur die Root-Suite.

## Filament & Moox Resources

Stack: **Filament 4**, **Livewire 3**, **Tailwind 4**, **Alpine.js**.

### Resource-Hierarchie

Moox-Resources erweitern die passende Basis — **nicht** direkt `Filament\Resources\Resource`:

| Basis-Klasse | Verwendung |
|--------------|------------|
| `Moox\Core\Entities\BaseResource` | Generische Resources |
| `Moox\Core\Entities\Items\Item\BaseItemResource` | Item-Entities |
| `Moox\Core\Entities\Items\Record\BaseRecordResource` | Record-Entities |
| `Moox\Core\Entities\Items\Draft\BaseDraftResource` | Draft/Versionierbare Entities |

Vor einer neuen Resource: **bestehende Resource im gleichen Package-Typ** als Vorlage lesen.

### Filament 4 Patterns

- **Form**: `public static function form(Schema $schema): Schema` — Komponenten via `Filament\Schemas\Schema`, Layout via `Grid`, `Section` aus `Filament\Schemas\Components`.
- **Table**: `public static function table(Table $table): Table` — Spalten, Filter, Actions.
- **Pages**: eigene Klassen unter `Resources/<Name>Resource/Pages/` (`List*`, `Create*`, `Edit*`, `View*`).
- **Actions**: Filament 4 Actions (`Filament\Actions\*`) — nicht veraltete `Tables\Actions`.
- **Scoped Resources**: `HasScopedChildResource`, `ScopedResourceConfiguration::make()` wo im Package etabliert.
- **Tabs**: `HasResourceTabs` Trait für tab-basierte Resources.

### UI-Regeln

- **Filament-Komponenten** (`x-filament::*`) und `fi-*`-Klassen — kein paralleles UI-Framework.
- **Geschäftslogik nicht in Resources** — komplexe Logik in Actions/Services; Resource definiert nur Schema, Table, Navigation.
- **Formular 1:1 wiederverwenden** — bei Tree-Inspector unverändert `Resource::form()` der Quell-Resource; keine Action-Umverdrahtung.
- **Keine neuen Buttons/Footer-Actions** ohne Rückfrage und Bestätigung (siehe Tree-Regeln).
- Domänen-Filter über `getEloquentQuery()`, `modifyQuery`-Closures oder Resource-Hooks — nicht hardcoded in generischen Packages.

### Tree-Resources

Baum-Index über `packages/tree` — Consumer implementieren nur `ConfiguresTreeIndex` + `treeIndex()`. Details: `.cursor/rules/moox-tree-integration.mdc`.

## Was du vermeidest

- Neue Funktion schreiben, ohne zuerst zu suchen.
- Copy-Paste zwischen Packages.
- Business-Logik in Views oder UI-Komponenten.
- Scope-Creep und unaufgeforderte Refactorings.
- Commits oder PRs ohne ausdrückliche Aufforderung.
- Filament-Resources direkt von `Resource` erben statt von Moox-Basisklassen.
- Veraltete Filament-3-Patterns (`Forms\Form` statt `Schemas\Schema`).
