---
name: PHPStan Level 5
overview: "Phase 1 (jetzt): Nur Larastan in die Root-PHPStan-Konfiguration einbinden und analysieren lassen, um den Fehlerberg einzuordnen. Alle weiteren Fixes (WP, BPMN, Builder, Filament, …) bewusst zurückstellen bis du den Überblick nach Larastan hast."
todos:
  - id: wire-larastan
    content: "Phase 1: vendor/larastan/larastan/extension.neon in phpstan.neon.dist includes; paths/bootstrap nur falls nötig; phpstan analyse zur Bestandsaufnahme"
    status: pending
  - id: wp-boundary
    content: "Phase 2 (nach Überblick): WP-Stubs oder excludePaths"
    status: cancelled
  - id: fix-bpmn-jobmonitor
    content: "Phase 2: BPMN-ServiceProvider + JobMonitor-Query"
    status: cancelled
  - id: builder-carbon-deps
    content: "Phase 2: Builder/Carbon/Composer-Deps"
    status: cancelled
  - id: filament-pages
    content: "Phase 2: Filament @property / Basis"
    status: cancelled
  - id: trait-unused
    content: "Phase 2: Trait-unused"
    status: cancelled
isProject: false
---

# PHPStan — Phase 1 nur Larastan

## Aktueller Auftrag

**Zuerst ausschließlich Larastan aktivieren** ([`phpstan.neon.dist`](phpstan.neon.dist): `includes:` um `vendor/larastan/larastan/extension.neon`, analog zu [`packages/devtools/phpstan.neon`](packages/devtools/phpstan.neon)). Danach **`vendor/bin/phpstan analyse`** laufen lassen (lokal ideal nach `composer install` / wie CI nach `php dev.php`), um zu sehen, **welche** Meldungen nach Larastan noch übrig sind.

Keine WordPress-Stubs, keine Code-Fixes, keine Baseline in dieser Phase — nur Konfiguration + Bestandsaufnahme.

## Warum das der richtige erste Schritt ist

Ohne Larastan fehlen Laravel-spezifische Typinformationen: Eloquent-Magic (`where`, `find`), Facades (`DB`, `Log`, `Schema`), stark vereinfachte `auth()`-Typen usw. Das erzeugt eine große Lawine, die Larastan typischerweise stark reduziert. Erst danach lohnt sich der Überblick über „echte“ Restfehler.

## Technische Hinweise für Phase 1

- **`paths`**: zunächst bei `packages` lassen (wie heute), damit Runs ohne generiertes Laravel-Skeleton nicht scheitern. Wenn Larastan nach dem Include **fehlermeldet** (Container/Application), CI hat ohnehin `php dev.php` — optional dann `app`/`bootstrap` ergänzen **wenn** diese Verzeichnisse existieren, oder nur in CI dokumentieren.
- **[`phpstan-baseline.neon`](phpstan-baseline.neon)** weiterhin nicht befüllen (deine Vorgabe).

## Diagnose (Kontext, unverändert)

- [`phpstan.neon.dist`](phpstan.neon.dist) enthält aktuell **kein** Larastan-Include — nur eine leere Baseline-Datei.
- WordPress-Hooks in `packages/press/src/*Plugin.php` und echte Code-Stellen (z. B. BPMN-Singleton, `JobManager::getModel()`) sind **nicht** Teil von Phase 1; sie bleiben als Restfehler-Normalfall nach Larastan bestehen, bis du Phase 2 startest.

## Phase 2 (bewusst zurückgestellt)

Nach deinem Überblick: WP-Grenze (Stubs/`excludePaths`), echte Bugfixes (BPMN, Jobs), Query-vs-Eloquent-Builder, Carbon, fehlende Dependencies, Filament-Dynamic-Properties, Trait-unused — wie zuvor beschrieben, aber **erst nach** Larastan-Bestandsaufnahme.
