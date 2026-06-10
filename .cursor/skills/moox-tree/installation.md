# Installation & Assets

Complete setup for `moox/tree` before integrating a consumer resource. Run these steps **once per host application** (and declare the dependency in the consumer package).

Placeholder: `{package}` = consumer package name (e.g. `category`).

---

## Prerequisites

| Requirement | Notes |
|-------------|-------|
| Laravel ^12 | Same as the Moox host app |
| Filament ^4 | Panels, Livewire, Filament assets pipeline |
| `moox/core` | Required by `moox/tree` (transitive via Composer) |
| `kalnoy/nestedset` | Only when using **Nested Set** mode |
| `moox/localization` | Only when the Filament table toolbar **language switcher** is enabled |

---

## Step 1 — Declare the Composer dependency

### Host application (Laravel app)

```bash
composer require moox/tree:@dev
```

In the **Moox monorepo**, path repositories for `packages/*` are already configured in the root `composer.json`. The package lives at `packages/tree`.

### Consumer package (`packages/{package}/composer.json`)

Add `moox/tree` to `require` so the dependency is explicit for anyone using your package:

```json
"require": {
    "moox/core": "dev-main",
    "moox/tree": "dev-main"
}
```

Then from the **host app root**:

```bash
composer update moox/{package}
```

**Nested Set only:**

```bash
composer require kalnoy/nestedset
```

**Language switcher in toolbar** (when `forwardFromResource(..., useFilamentTableToolbar: true)` and language switcher enabled): ensure `moox/localization` is installed and its views are available (`localization::lang-selector`).

---

## Step 2 — Service provider (auto-discovery)

`Moox\Tree\TreeServiceProvider` registers via Laravel package discovery (`composer.json` → `extra.laravel.providers`).

Verify it is loaded:

```bash
php artisan package:discover
```

If auto-discovery is disabled, register manually in `bootstrap/providers.php`:

```php
Moox\Tree\TreeServiceProvider::class,
```

### What the provider registers

| Asset / component | Mechanism | Consumer action |
|-------------------|-----------|-----------------|
| **CSS** `tree-index` (`resources/css/tree.css`) | `FilamentAsset::register()` under package `moox/tree` | Run `php artisan filament:assets` (see Step 3) |
| **Alpine store** `$store.filamentTreeIndex` | `PanelsRenderHook::SCRIPTS_BEFORE` → `scripts/alpine-tree-store` blade | None — loaded on every Filament panel page |
| **Livewire** `ResourceTreeIndex` | `Livewire::component()` (alias from config, default `filament-tree-index`) | None |
| **Blade views** `filament-tree-index::*` | `loadViewsFrom()` | None unless you publish overrides |
| **Config** `filament-tree-index` | `mergeConfigFrom()` | Optional publish (Step 5) |
| **Language switcher hook** | `TablesRenderHook::TOOLBAR_SEARCH_BEFORE` when toolbar + switcher enabled | Requires `moox/localization` view |

**Do not** copy `tree.css` or the Alpine store into the consumer package. The tree UI is a single source of truth in `packages/tree`.

---

## Step 3 — Publish Filament assets (required)

Filament must compile and serve the registered CSS. **Without this step the tree renders unstyled** (broken layout, missing `fi-tree-*` classes).

```bash
php artisan filament:assets
```

Run again after:

- Upgrading `moox/tree`
- Changing `packages/tree/resources/css/tree.css`
- Deploying to a new environment

### No extra frontend build for tree layout

Tree layout classes (`fi-tree-*`) live in the package CSS file. **No** additional Tailwind `@source` or Vite entry is required in the consumer for the tree index UI.

If your host app runs `npm run build` for its own theme, that is unrelated to tree assets — tree CSS is delivered through Filament’s asset pipeline, not your app’s Vite bundle.

---

## Step 4 — Verify installation

Quick checks before writing integration code:

```bash
# Package installed
composer show moox/tree

# Provider discovered (provider list includes Moox\Tree\TreeServiceProvider)
php artisan package:discover

# Filament assets published (compiles tree-index CSS into public)
php artisan filament:assets
```

**Browser smoke test** (after resource integration): open the tree list page and confirm:

- [ ] Two-column layout (tree left, inspector right)
- [ ] Expand/collapse and selection work
- [ ] No unstyled raw HTML / missing spacing
- [ ] Drag handle appears when `reorderable(true)` (if applicable)

**DevTools checks** if layout is broken:

- Network: Filament theme CSS includes `tree-index` / `tree.css`
- Console: no errors about `$store.filamentTreeIndex` being undefined
- Elements: nodes use `fi-tree-*` classes

---

## Step 5 — Optional configuration publish

Only when you need to override defaults:

```bash
# Config: authorization, Livewire alias
php artisan vendor:publish --tag=filament-tree-index-config

# Views: override package Blade templates (rare)
php artisan vendor:publish --tag=filament-tree-index-views
```

Default config (`config/filament-tree-index.php`):

| Key | Default | Purpose |
|-----|---------|---------|
| `authorization.enabled` | `true` | Tree CRUD permission checks |
| `livewire.alias` | `filament-tree-index` | Livewire component name |

After publishing config, clear config cache if enabled:

```bash
php artisan config:clear
```

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Unstyled tree, no columns | `filament:assets` not run | `php artisan filament:assets` |
| Alpine errors, expand/collapse dead | Provider not loaded | `composer dump-autoload` + `php artisan package:discover` |
| Livewire component not found | Missing require or cache | `composer require moox/tree:@dev`, `php artisan optimize:clear` |
| Language switcher missing | `moox/localization` not installed or view missing | `composer require moox/localization`, or set `filamentTableLanguageSwitcher(false)` |
| Stale CSS after package edit | Asset cache | `php artisan filament:assets` + hard refresh browser |
| 419 on inspector form | Unrelated to assets — see `packages/tree/README.md` embedded form transport | Use `RendersAsTreeIndexInspector` trait |

---

## Installation checklist (before integration code)

Use this before proceeding to [integration.md](integration.md):

- [ ] `moox/tree` in host app **and** consumer `composer.json` `require`
- [ ] `composer update` completed without errors
- [ ] `Moox\Tree\TreeServiceProvider` discovered
- [ ] `php artisan filament:assets` executed successfully
- [ ] Nested Set: `kalnoy/nestedset` installed (if applicable)
- [ ] Language switcher: `moox/localization` available (if toolbar language option is Yes)
- [ ] No duplicate tree CSS/JS/Alpine store in consumer package

Next: [integration.md](integration.md) — resource templates and verification.
