# Block Editor Package

Moox-Standards: siehe Repo-Root [`AGENTS.md`](../../AGENTS.md). Laralib-Standards (Vault): `agent/INDEX.md` — senior-engineering-standard, clean-code, code-principles, phpstan.

## Zweck

Wiederverwendbarer JSON-Block-Editor für Filament/Livewire mit serverseitigem Rendering und **Dynamic-Feed**-Blöcken (`EntityQuerySourceRegistry`). Drei Schichten: Admin-Editor (Alpine.js), Editor-API (Templates), Public Rendering (`BlockContentRenderer`).

## Struktur

- `src/Forms`, `src/Livewire` — Filament-Feld `BlockEditor` + Livewire-State
- `src/Rendering` — `BlockContentRenderer`, `BlockRenderer`-Contracts
- `src/EntityQuery` — Dynamic-Feed-Registry, Query-Builder, Sources
- `src/Http` — Template-API (Controller, Requests)
- `src/Repositories` — Datenzugriff (z. B. `TemplateRepository`)
- `src/Filament` — interne `TemplateResource` (Referenz-Integration)
- `resources/editor` — Frontend-Editor (JS/Alpine)
- `docs/DEVELOPER.md` — Maintainer-Handbuch (Architektur, Extension Points)
- `.cursor/skills/moox-block-editor` — Integrator-Skill (nach API-/Install-Änderungen syncen)

## Regeln

- Vor neuem Code: bestehende Klassen in diesem Package, Consumer (`packages/news`, `packages/page`) und `moox/core` prüfen (DRY).
- `declare(strict_types=1);` in jeder PHP-Datei unter `src/`.
- Geschäftslogik in Actions/Services/Repositories — nicht in Livewire, Blade oder JS.
- Neue Block-Typen: Editor in `resources/editor/components/blocks`, PHP-Renderer in `src/Rendering/Blocks` + Registrierung im `BlockEditorServiceProvider`.
- Dynamic-Feed-Sources nur per `EntityQuerySourceRegistry::register()` in Consumer-Packages (`config/{pkg}.php` → `dynamic_feed`); global defaults in `config/moox-editor.php` → `dynamic_feed.*`; `class_exists`-Guard wenn block-editor optional.
- `TemplateResource` erbt bewusst direkt von `Filament\Resources\Resource` (internes Package-Admin-UI, keine Moox-Entity).
- PHPStan: Moox-Monorepo Level 5 (`composer analyse`); Zielbild Laralib Level 10.
- Tests für neues Verhalten (Pest unter `tests/`; JS unter `tests/js/`).
- Pint vor Commit: `vendor/bin/pint --dirty`.

## Tests

```bash
php artisan test --compact packages/moox/block-editor/tests
node --test packages/moox/block-editor/tests/js/*.test.mjs
composer analyse
```

## Host-App

- Registriert via Composer path repository und `BlockEditorServiceProvider`.
- Assets: `php artisan vendor:publish --tag=moox-editor-assets`
- Skill-Sync: bei integrator-relevanten Änderungen Regel `moox-package-skill-sync` und Skill `moox-block-editor` prüfen.

## Siehe auch

- `README.md` — Installation und Quickstart
- `docs/DEVELOPER.md` — Architektur und Extension Points
- `API.md` — Template-API
