# Moox Block Editor

Reusable JSON block editor for Laravel, Filament, and Livewire.  
Embeds the editor from `resources/editor` as a Filament form field and renders stored JSON on public pages.

## Requirements

- **PHP**: ^8.3
- **Laravel**: ^12 || ^13
- **Filament**: ^4.0 (`Filament\Schemas\Schema`, `Filament\Forms\Components\*`)
- **Livewire**: ^4.0
- **Tailwind CSS / Alpine.js**: loaded in the editor frontend via CDN (see `resources/editor/README.md`)

## Quickstart

```bash
composer require moox/block-editor
php artisan vendor:publish --tag=moox-editor-assets
php artisan migrate
```

### Local development (path repository)

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/block-editor",
    "options": { "symlink": true }
  }
],
"require": {
  "moox/block-editor": "dev-main"
}
```

### Filament 4 field

```php
use Filament\Schemas\Schema;
use Moox\BlockEditor\Forms\Components\BlockEditor;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        BlockEditor::make('content')
            ->label('Content')
            ->columnSpanFull(),
    ]);
}
```

Store `content` as JSON (`TEXT`/`JSON`) or cast to `array` on the model.

### Public rendering

```blade
<x-moox-editor::block-content :content="$translation->content" :locale="$locale" />
```

### Dynamic feeds (consumer package)

Dynamic feeds use **two config layers**:

| Layer | Location | Keys |
|-------|----------|------|
| **Global defaults** | `config/moox-editor.php` → `dynamic_feed` | `max_limit`, `default_limit`, `default_order_by`, `default_order_direction` |
| **Source definition** | Consumer package, e.g. `config/news.php` → `dynamic_feed` | `model`, `views`, `filter_schema`, `sortable_columns`, `feed_item_mapper`, … |

Publish global defaults (optional):

```bash
php artisan vendor:publish --tag=moox-editor-config
```

Register the source in the consumer `ServiceProvider`:

```php
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;

if (class_exists(EntityQuerySourceRegistry::class)) {
    EntityQuerySourceRegistry::register('news', config('news.dynamic_feed', []));
}
```

There is **no HTTP API** for dynamic feeds. The Filament field embeds registered sources server-side via `DynamicFeedEditorCatalog` and `data-dynamic-feed-sources` on the editor root element.

Full step-by-step (config reference, mapper, Blade views): [docs/DEVELOPER.md § 9](docs/DEVELOPER.md). Gold-standard consumer: `packages/news`.

## Documentation

| Document | Purpose |
|----------|---------|
| [docs/DEVELOPER.md](docs/DEVELOPER.md) | Architecture, public rendering, dynamic feeds, extension points |
| [API.md](API.md) | Template API (routes, auth, payloads) |
| [COMPONENT_DEVELOPMENT_GUIDELINE.md](COMPONENT_DEVELOPMENT_GUIDELINE.md) | Reuse-first rules for editor components |
| [resources/editor/README.md](resources/editor/README.md) | Frontend editor, `data-*` flags, JSON workflow |
| [resources/editor/STRUCTURE.md](resources/editor/STRUCTURE.md) | Editor folder structure |
| [tests/README.md](tests/README.md) | PHP and JS test commands |
| [AGENTS.md](AGENTS.md) | Agent rules for this package |
| [CHANGELOG.md](CHANGELOG.md) | Release notes |

Integrator skill: `.cursor/skills/moox-block-editor` (checklist for Filament field, dynamic feeds, public pages).

Gold-standard dynamic feed consumer: `packages/news`.

## Typical steps

1. `composer require moox/block-editor`
2. `php artisan vendor:publish --tag=moox-editor-assets`
3. `php artisan migrate`
4. Add `BlockEditor::make('content')` to your Filament 4 resource `Schema::components`
5. Render on public pages with `<x-moox-editor::block-content>` (optional)
6. Register dynamic feed sources in consumer packages (see [docs/DEVELOPER.md](docs/DEVELOPER.md))
