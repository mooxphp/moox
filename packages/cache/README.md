Overview

Build Moox Cache as a Filament 5 plugin with three core surfaces:

1. Cache Manager page: cards/actions for Laravel cache, config, route, view, event, queue/restart, optimize clear, custom keys.
2. Dashboard widget: cache health/status, configured drivers, last clear, quick actions.
3. Top-right clear button: global panel action/dropdown for “clear all” and selected targets.

Reference features:

- Cache Manager supports Filament v5, access callbacks, custom navigation, predefined cache-key cards, and forget/flush actions. ￼
- Clear Cache Button supports Filament 5, calls optimize:clear by default, and allows adding custom Artisan commands like page-cache:clear. ￼
- Joseph Silber Page Cache stores responses as static files and clears via page-cache:clear, optionally by slug and --recursive. ￼
- Cloudflare target should support purge all/files/tags/hosts, cache rules, analytics widgets, and API-token credentials. ￼
- monicahq/laravel-cloudflare is mainly for trusted Cloudflare proxies/IP cache, Laravel 13-compatible, not cache purging. ￼
- ziffmedia/laravel-cloudflare is useful conceptually for URL/tag purging and Cache-Tag headers, but it is Nova-oriented. ￼

Proposed package architecture

Package name: moox/cache
Namespace: Moox\Cache

Addons:
moox/cache-static
moox/cloudflare (including cf cache plugin)


Core idea: every clearable thing is a CacheTarget.

interface CacheTarget
{
public function key(): string;
public function label(): string;
public function description(): ?string;
public function category(): string;
public function icon(): ?string;
public function color(): ?string;
public function status(): CacheTargetStatus;
public function clear(CacheClearRequest $request): CacheClearResult;
}

Built-in targets:

- application-cache: cache:clear
- config-cache: config:clear
- route-cache: route:clear
- view-cache: view:clear
- event-cache: event:clear
- compiled: clear-compiled
- optimize-clear: optimize:clear
- custom-key: Cache::forget($key)
- cache-store-flush: Cache::store($store)->flush()

Extensions:

- moox/cache-page: adapter for JosephSilber/page-cache
- moox/cache-cloudflare: Cloudflare API adapter
- optional third-party adapters via service provider registration

Filament plugin API

CachePlugin::make()
->canAccess(fn () => auth()->user()?->can('manage cache'))
->showNavigation()
->showDashboardWidget()
->showTopbarButton()
->targets([
ApplicationCacheTarget::make(),
OptimizeClearTarget::make(),
])
->cacheKeys([
CacheKey::make('homepage')->label('Homepage')->description('Homepage fragments'),
CacheKey::make('settings')->label('Settings'),
]);

UI plan

Page: “Cache”

- grouped cards: Laravel, Stores, Keys, Page Cache, Cloudflare
- actions: Clear, Clear selected, Forget key, Flush store
- result modal with command output, duration, success/failure
- permissions per target

Dashboard widget

- current cache driver/store
- page-cache enabled/status
- Cloudflare credentials/status
- last clear events
- quick “Clear all” / “Clear page cache” / “Purge Cloudflare” actions

Top-right button

- dropdown action in panel header
- default action: optimize:clear
- expandable actions: Laravel cache, page cache, Cloudflare purge
- badge/counter optional, similar to the referenced clear-cache button pattern. ￼

Cloudflare design

Do not make Cloudflare mandatory. Add it as an optional adapter.

Features:

- purge everything
- purge by URL/files
- purge by tags
- purge by hosts
- optional analytics widget
- token + zone ID config
- domain allow-list before purging URLs, inspired by ziffmedia’s domains safety option. ￼

Use direct Cloudflare API internally, or wrap a small client, because:

- monicahq/laravel-cloudflare solves trusted proxies/IP blocks, not purge management. ￼
- notwonderful’s Filament plugin is broader than needed; Moox Cache should focus on cache only. ￼

Development phases

Phase 1: Core

- package skeleton
- config
- service provider
- CachePlugin
- target registry
- Laravel cache targets
- Filament page
- tests for command execution and permissions

Phase 2: UX

- topbar clear button
- dashboard widget
- cache-key cards
- action result history
- notifications

Phase 3: Page Cache

- detect page-cache:clear
- clear all
- clear slug
- recursive clear
- optional UI form for slug/path

Phase 4: Cloudflare

- Cloudflare credentials config
- purge all/files/tags/hosts
- domain validation
- connection test
- optional analytics widget

Phase 5: Moox polish

- translations
- dark mode
- Moox branding/icons
- policies
- docs
- screenshots
- Filament plugin listing

Recommended MVP

Ship first with:

- Cache Manager page
- topbar optimize:clear button
- dashboard widget
- custom cache keys
- extension registry

Then add Page Cache and Cloudflare as optional modules.

## Packages

| Package | Purpose |
|---------|---------|
| `moox/cache` | Core targets, registry, Filament Cache Manager page |
| `moox/cache-static` | Page cache targets (`page-cache:clear`) |
| `moox/cloudflare` | Cloudflare purge targets and API client |

## Quick start

```bash
composer require moox/cache
```

Register on your Filament panel:

```php
use Moox\Cache\Plugins\CachePlugin;

$panel->plugins([
    CachePlugin::make()
        ->canAccess(fn () => auth()->user()?->can('manage cache'))
        ->cacheKeys([
            CacheKey::make('homepage')->label('Homepage'),
        ]),
]);
```

Optional extensions:

```bash
composer require moox/cache-static moox/cloudflare
```

```php
use Moox\Cloudflare\CloudflareCachePlugin;

$panel->plugins([
    CachePlugin::make(),
    CloudflareCachePlugin::make(),
]);
```

Enable Cloudflare in `.env`:

```env
CLOUDFLARE_CACHE_ENABLED=true
CLOUDFLARE_API_TOKEN=
CLOUDFLARE_ZONE_ID=
CLOUDFLARE_ALLOWED_DOMAINS=example.com
```
