---
name: moox-block-editor
description: >-
  Integrates moox/block-editor (Filament field, editor assets, dynamicFeed blocks,
  EntityQuerySourceRegistry, public BlockContentRenderer). Use when the user says
  moox/block-editor, block editor, dynamicFeed, dynamic feed, EntityQuery, or
  wants to register entity feeds (e.g. news) for the editor.
---

# Moox Block Editor — package integration

Reusable JSON block editor for Filament/Livewire plus server-side rendering and **dynamic entity feeds** (`dynamicFeed`).

Canonical docs:

- [packages/block-editor/README.md](../../../packages/block-editor/README.md) — install and quickstart
- [packages/block-editor/docs/DEVELOPER.md](../../../packages/block-editor/docs/DEVELOPER.md) — architecture, rendering, dynamic feeds
- [packages/block-editor/API.md](../../../packages/block-editor/API.md)
- Gold-standard dynamic feed consumer: `packages/news` (`config/news.php`, `NewsServiceProvider`, `NewsFeedItemMapper`, Blade views)

## When to use

| User intent | Action |
| --- | --- |
| Add block editor to a Filament resource | `BlockEditor::make('content')` + DB column cast `array` |
| Publish editor assets | `php artisan vendor:publish --tag=moox-editor-assets` |
| Register dynamic feed source (news, …) | Config `dynamic_feed` + `EntityQuerySourceRegistry::register()` — [integration.md](integration.md) |
| Render blocks on public pages | `<x-moox-editor::block-content>` — [integration.md § Public rendering](integration.md#public-rendering) |
| Page frontend route | `moox/page` `PageController` + `moox::page.default` — [integration.md § Page frontend](integration.md#page-frontend) |

## Integration workflow (checklist)

```
- [ ] 1. Inspect consumer: model (draft/translations), content column type, Filament resource
- [ ] 2. composer require moox/block-editor (+ moox/frontend for public pages)
- [ ] 3. Publish assets; ensure content column stores JSON array
- [ ] 4. BlockEditor field in Filament 4 resource `Schema::components`
- [ ] 5. (Optional) dynamic_feed config + registry for entity blocks
- [ ] 6. (Optional) Blade feed views + FeedItemMapper
- [ ] 7. Public template: moox::page.default or block-content component
- [ ] 8. Pest tests for new behavior
```

**Do not** hard-require `moox/block-editor` in optional consumers without `class_exists(EntityQuerySourceRegistry::class)` guard.

## Quick reference

### Filament 4 field (`Schema::components`)

```php
use Filament\Schemas\Schema;
use Moox\BlockEditor\Forms\Components\BlockEditor;

BlockEditor::make('content')
    ->label(__('core::core.content'))
    ->columnSpanFull();
```

Place inside `public static function form(Schema $schema): Schema`.

Translation model: `'content' => 'array'` cast + fillable.

### Dynamic feed registry (consumer ServiceProvider)

```php
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;

if (class_exists(EntityQuerySourceRegistry::class)) {
    EntityQuerySourceRegistry::register('news', config('news.dynamic_feed', []));
}
```

Use **string** for `feed_item_mapper` in config (not `::class`) to avoid autoload when block-editor is absent.

### Public rendering

```blade
<x-moox-editor::block-content :content="$translation->content" :locale="$locale" />
```

Locale: **Variant B** — explicit pass-through via `BlockEditorLocale::resolveActive()` / `RenderContext`; no silent fallback to other locales in sources (v1).

### Dynamic feed editor config

No HTTP API. The Filament field embeds registered sources via `data-dynamic-feed-sources` (built by `DynamicFeedEditorCatalog` in `block-editor-field.blade.php`).

## Tests

```bash
php artisan test --compact packages/block-editor/tests/Feature
php artisan test --compact packages/page/tests/Feature/PageFrontendTest.php
node --test packages/block-editor/tests/js/*.test.mjs
```

## Additional resources

- Consumer templates: [integration.md](integration.md)
- Locale, registry, XSS, limits: [decisions.md](decisions.md)
