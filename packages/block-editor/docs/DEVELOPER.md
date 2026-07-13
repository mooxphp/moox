# Block Editor — Developer Handbook

Maintainer documentation for `moox/block-editor`. For installation and quickstart see [README.md](../README.md).

## Wichtige Klassen

| Klasse | Rolle |
|--------|--------|
| `BlockEditorServiceProvider` | Service Provider, Renderer-Registry, Asset-Publishing |
| `Forms\Components\BlockEditor` | Filament-Feld |
| `Livewire\BlockEditorField` | Livewire-State für Editor-JSON |
| `Rendering\BlockContentRenderer` | Serverseitiges Block-Rendering |
| `EntityQuery\EntityQuerySourceRegistry` | Dynamic-Feed-Source-Registry |
| `Repositories\TemplateRepository` | Template-Persistenz (API) |
| `Http\Controllers\TemplateController` | Template-API (dünn, delegiert an Repository) |

## Testing

```bash
php artisan test --compact packages/moox/block-editor/tests
node --test packages/moox/block-editor/tests/js/*.test.mjs
composer analyse
```

## Release-Checkliste

- [ ] Tests grün
- [ ] PHPStan ohne neue Fehler (`composer analyse`)
- [ ] README.md und dieses Handbuch aktuell
- [ ] CHANGELOG.md bei Release
- [ ] Skill `moox-block-editor` bei integrator-relevanten Änderungen syncen

---
## Package Overview

`moox/block-editor` spans three cooperating layers. Use this section as a map before diving into installation or the detailed chapters below.

### Architecture at a glance

| Layer | Runtime | Purpose | Entry points |
|-------|---------|---------|--------------|
| **Admin editor** | Browser (Alpine.js) | Authors edit block JSON in Filament | `BlockEditor` field, `public/vendor/moox/block-editor/*` |
| **Editor API** | Laravel (authenticated) | Template CRUD for the editor UI | `routes/api.php`, `API.md` |
| **Dynamic feed catalog** | Laravel (server-side, on field render) | Source metadata + filter options for `dynamicFeed` blocks | `DynamicFeedEditorCatalog`, `data-dynamic-feed-sources` |
| **Public rendering** | Laravel (server-side) | Turn stored JSON into HTML on public pages | `BlockContentRenderer`, `<x-moox-editor::block-content>` |

Data always flows through **JSON block trees**. The admin editor writes JSON; public pages read JSON and render HTML. Dynamic feed blocks store query configuration only — entity rows are resolved at render time (section 9).

```
Filament form (BlockEditor)
  └─ Livewire state → JSON string in DB

Public page / Blade
  └─ BlockContentRenderer → HTML per block type

Editor UI (optional)
  └─ Template API (auth required)
  └─ Dynamic feed sources embedded in Filament field (no HTTP API)
```

### PHP modules (where to look in code)

| Area | Key classes | Details in |
|------|-------------|------------|
| Filament field | `Forms\Components\BlockEditor`, `Livewire\BlockEditorField` | Section 4, `resources/editor/README.md` |
| Templates (DB + API) | `Models\Template`, `Http\Controllers\TemplateController` | `API.md` |
| Public rendering | `Rendering\BlockContentRenderer`, `Rendering\Contracts\BlockRenderer` | Section 8 |
| Locale | `Support\BlockEditorLocale` | Section 8.4 |
| Dynamic feeds | `EntityQuery\EntityQuerySourceRegistry`, `EntityQuery\EntityQueryBuilder` | Section 9 |
| Feed rendering | `Rendering\Blocks\DynamicFeedBlockRenderer` | Sections 8 + 9 |

Block JSON shape and available editor block types are documented in `resources/editor/README.md` and `resources/editor/components/blocks/README.md` — not duplicated here.

### Configuration (`config/moox-editor.php`)

The config file is registered by `BlockEditorServiceProvider`. Publish it when you need to change API routes or global feed defaults:

```bash
php artisan vendor:publish --tag=moox-editor-config
```

| Key | Purpose |
|-----|---------|
| `api.prefix` | Route prefix (default `api/editor`) |
| `api.version` | API version segment (default `v1`; set `''` to omit) |
| `api.middleware` | Middleware stack for editor API routes (`web`, `auth`, `throttle:60,1` by default) |
| `api.authorization` | Policy enforcement: `true`, `false`, or `null` (auto: on when middleware is set) |
| `dynamic_feed.max_limit` | Hard cap for feed `limit` (default `50`) |
| `dynamic_feed.default_limit` | Fallback when block omits `limit` (default `5`) |
| `dynamic_feed.default_order_by` | Fallback sort key (default `published_at`) |
| `dynamic_feed.default_order_direction` | Fallback direction (default `desc`) |

Full dynamic feed source config (`model`, `views`, `filter_schema`, …) lives in **consumer package config** (for example `config/news.php`), not in `moox-editor.php`. See section 9.

### HTTP API (summary)

Base URL pattern: `{api.prefix}/{api.version}` → default `/api/editor/v1`.

| Group | Endpoints | Used by | Reference |
|-------|-----------|---------|-----------|
| **Templates** | `GET/POST/PATCH/PUT/DELETE /templates` | Editor theme/template toolbar | `API.md` |

All template routes use the middleware from `api.middleware`. Authorization follows `api.authorization` via `Support\ApiAuthorization`.

**Dynamic feeds** have no HTTP endpoints. Sources are resolved at field-render time by `DynamicFeedEditorCatalog` and passed to the editor as `data-dynamic-feed-sources` (see `resources/views/livewire/block-editor-field.blade.php`).

Editor frontend URL resolution (templates only):

- Templates: `data-templates-api-url` → `window.mooxEditorTemplatesApiUrl` → fallback `/api/editor/v1/templates`

### Locale handling (summary)

`Moox\BlockEditor\Support\BlockEditorLocale` is shared by public rendering, dynamic feed queries, and filter options.

- **`resolveActive($request?)`** — active locale from `?lang=`, request input, `app()->getLocale()`, or `config('app.locale')`
- **`resolveTranslationLocale($locale)`** — maps short codes (`de`) to variants (`de_DE`) when possible
- **`localeCandidates($locale)`** — locale list for translation queries and feed mappers

Use the same resolved locale in your controller, translation lookup, and `<x-moox-editor::block-content>`. Details: section 8.4.

### Public rendering (summary)

| Piece | Role |
|-------|------|
| `BlockContentRenderer` | Walks block JSON, dispatches to renderers |
| `BlockRenderer` contract | One renderer per block type (`supports()` + `render()`) |
| `<x-moox-editor::block-content>` | Blade wrapper for templates |

Shipped renderers: `paragraph`, `heading1`–`heading6`, `dynamicFeed`. Other editor block types need custom renderers or only render via `children` recursion. Details: section 8.

### Dynamic feeds (summary)

Define entity sources in `packages/block-editor/config/dynamic-feed-sources.php`. They are merged into `dynamic_feed.sources` and registered automatically at boot via `DynamicFeedSourceRegistrar`:

```php
// config/moox-editor.php
'dynamic_feed' => [
    'sources' => [
        'news' => [
            'enabled' => true,
            'model' => News::class,
            // ...
        ],
    ],
],
```

The editor receives sources from the embedded catalog when the Filament field renders; public pages resolve data through `DynamicFeedBlockRenderer`. Details: section 9.

### Choose your next step

| Goal | Start here |
|------|------------|
| Install in a Laravel app | Section 1 → 7 |
| Add field to Filament 4 resource | Section 4 |
| Show blocks on a public page | Section 8 |
| Register news/article feeds | Section 9, `packages/block-editor/config/dynamic-feed-sources.php` |
| Extend editor block types (JS) | `resources/editor/components/blocks/README.md` |
| Template CRUD / API integration | `API.md` |
| Run or add tests | `tests/README.md` |

---

## 1. Installation via Composer

### 1.1 Standard Installation (from Packagist or similar)

Add the package to your project:

```bash
composer require moox/block-editor
```

Laravel discovers the service provider automatically through `extra.laravel.providers` in the package's `composer.json`:

```json
"extra": {
  "laravel": {
    "providers": [
      "Moox\\BlockEditor\\BlockEditorServiceProvider"
    ]
  }
}
```

### 1.2 Local Development (`path` repository)

If you are developing the package locally, you can include it via a `path` repository as in the example project:

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/block-editor",
    "options": {
      "symlink": true
    }
  }
],
"require": {
  "moox/block-editor": "dev-main"
}
```

After that:

```bash
composer update moox/block-editor
```

---

## 2. Publish Assets

The editor ships with a ready-to-use static frontend (JS/CSS) that must be copied into the `public` directory.  
Use the `moox-editor-assets` publish tag registered by the service provider:

```bash
php artisan vendor:publish --tag=moox-editor-assets
```

The files will then be available under:

- `public/vendor/moox/block-editor/...`

When updating the package, you can overwrite the assets with `--force`:

```bash
php artisan vendor:publish --tag=moox-editor-assets --force
```

Make sure your web server serves the `public` directory.

---

## 3. Migrations and Routes

The `BlockEditorServiceProvider` loads migrations and API routes automatically:

- Migrations from `database/migrations`
- Routes from `routes/api.php`

After installation, or after updates that include new migrations, run:

```bash
php artisan migrate
```

once.

---

## 4. Usage in a Filament 4 Form

The package provides a Filament form field class named `Moox\BlockEditor\Forms\Components\BlockEditor`.  
You can use it in Filament 4 Resources and custom forms to embed a block editor field that stores JSON in a model attribute.

### 4.1 Field in a Filament Resource

Filament 4 Resources use `Filament\Schemas\Schema` instead of the Filament 3 `Form` class.  
The reference implementation is `Moox\BlockEditor\Filament\Resources\TemplateResource`.

```php
use Filament\Schemas\Schema;
use Moox\BlockEditor\Forms\Components\BlockEditor;

public static function form(Schema $schema): Schema
{
    return $schema
        ->columns(2)
        ->components([
            BlockEditor::make('content')
                ->label('Content')
                ->columnSpanFull()
                ->required(),
        ]);
}
```

In a custom Filament page or action form, use the same field class inside the schema components array.

### 4.2 Field configuration

`BlockEditor` maps fluent methods to editor `data-*` attributes (see `resources/views/forms/components/block-editor.blade.php`).

| Method | Purpose |
|--------|---------|
| `positiveBlock([...])` | Whitelist of allowed block type keys |
| `negativeBlock([...])` | Blacklist of hidden block type keys |
| `templates(bool)` | Enable theme/template toolbar tab |
| `templateSlug('…')` | Load a template by slug on init |
| `showJson(bool)` | Show developer JSON panel |
| `showJsonImport(bool)` | Enable JSON import (requires `showJson(true)`) |
| `addComponents(bool)` | Allow adding new blocks |
| `mediaLibraryApiUrl('…')` | Media library API endpoint |
| `mediaLibraryCollection('…')` | Default media collection |
| `mediaUsableType('…')` | `media_usables.media_usable_type` context |
| `mediaUsableId(…)` | `media_usables.media_usable_id` context |

Example with restricted blocks and media context:

```php
BlockEditor::make('content')
    ->label('Content')
    ->columnSpanFull()
    ->negativeBlock(['code', 'embed'])
    ->templates(false)
    ->mediaLibraryApiUrl('/api/media')
    ->mediaUsableType($this->getResource()::getModel())
    ->mediaUsableId(fn (): ?int => $this->getRecord()?->getKey()),
```

### 4.3 Field behavior / data format

The `BlockEditor` component ensures that the field state is always persisted as a **JSON string**:

- Empty state -> `'[]'`
- Already a string -> stored unchanged
- Array/Collection -> serialized via `json_encode(..., JSON_UNESCAPED_UNICODE)`

In your model, you would typically have a `content` column (for example of type `TEXT` or `LONGTEXT` / `JSON`) where the complete block tree is stored as JSON.

If you cast `content` to `array` in the model (as `Moox\BlockEditor\Models\Template` does), the field still dehydrates to a JSON string for storage. The public renderer accepts both JSON strings and arrays.

---

## 5. How It Works in the Livewire / Filament Context

Internally, a Livewire component view is used:

- View: `moox-editor::forms.components.block-editor`
- Livewire component: `Moox\BlockEditor\Livewire\BlockEditorField`

In the field Blade view (`resources/views/livewire/block-editor-field.blade.php`), the following happens among other things:

- The initial JSON state is derived from the field state (`$state`)  
- The static editor under `public/vendor/moox/block-editor` is included:
  - `styles/editor.css`
  - `block-editor-field.js`
  - `core/render/mount-editor.js`
  - `browser@4.js`
- Communication back to Livewire happens through a hidden input field with `wire:model.defer="state"`.

You normally do not need to customize this part. The important part is simply that the published assets are reachable.

---

## 6. Frontend Editor in Detail

The actual editor is a standalone, modular frontend located in:

- `resources/editor`

There you will find:

- `index.html`, `block-editor.js`, `styles/editor.css`
- `core/*` - state, storage, drag and drop, renderer, shortcuts, toolbar methods, utils  
- `components/*` - block implementations (text/media/layout/data), templates, block types, docs  
- `core/themes/*` - theme methods and extensions

A detailed description of the frontend and JSON format can be found in:

- `resources/editor/README.md`
- `resources/editor/STRUCTURE.md`

---

## 7. Typical Installation Steps (Overview)

1. **Install the package**
   - `composer require moox/block-editor`
2. **Publish assets**
   - `php artisan vendor:publish --tag=moox-editor-assets`
3. **Run migrations**
   - `php artisan migrate`
4. **Use the Filament 4 form field**
   - Use `BlockEditor::make('content')` inside `Schema::components([...])` in your resource/form
5. **Render on public pages (optional)**
   - Use `<x-moox-editor::block-content :content="..." :locale="..." />` (see section 8)
6. **Verify storage**
   - Make sure the database column (for example `content`) has sufficient size (`TEXT` / `LONGTEXT` / `JSON`)

At that point, the block editor is ready to use in your Filament / Laravel project.

---

## 8. Public Frontend Rendering

The block editor stores content as JSON. On public pages, that JSON is rendered **server-side** into HTML through `BlockContentRenderer` and the Blade component `<x-moox-editor::block-content>`.

This is separate from the admin editor frontend under `public/vendor/moox/block-editor`. Public rendering does not load Alpine.js or the editor assets.

### 8.1 How public rendering works

At render time the package:

1. normalizes the stored content (JSON string or array) into a block list
2. resolves the active locale (explicit argument or `BlockEditorLocale::resolveActive()`)
3. walks the block tree top to bottom
4. dispatches each block to the first matching `BlockRenderer`
5. concatenates the resulting HTML fragments

Nested blocks are supported in two ways:

- container blocks with a `children` array are recursed when no dedicated renderer exists
- dedicated renderers (for example `dynamicFeed`) render their own output and views

### 8.2 Blade component

The package registers the view component `moox-editor::block-content`.

```blade
<x-moox-editor::block-content
    :content="$translation->content"
    :locale="$locale"
/>
```

Props:

| Prop | Type | Description |
|------|------|-------------|
| `content` | `string\|array` | Block JSON from the database (string or decoded array) |
| `locale` | `string\|null` | Active locale for dynamic feeds and translated entities. Falls back to `BlockEditorLocale::resolveActive()` when omitted |

The component outputs unescaped HTML (`{!! ... !!}`) because paragraph blocks may contain intentional markup. Only use trusted editor content.

### 8.3 Content format

The renderer accepts:

- a JSON string (typical when the model stores raw JSON in a `TEXT` column)
- a PHP array (typical when the model casts `content` to `array`)

Invalid or empty input becomes an empty block list and renders nothing.

Minimal example:

```json
[
  {
    "id": "1",
    "type": "paragraph",
    "content": "<p>Hello world</p>"
  },
  {
    "id": "2",
    "type": "heading2",
    "content": "Section title"
  }
]
```

Each block should contain at least:

- `type` — block type key (for example `paragraph`, `heading2`, `dynamicFeed`)
- `id` — stable identifier (used in logs and editor state; not required for rendering, but recommended)

Optional fields used by renderers:

- `content` — HTML or plain text payload
- `classes` — Tailwind/CSS classes applied by PHP renderers
- `children` — nested blocks for container types
- dynamic feed fields — see section 9

### 8.4 Locale resolution

Use `Moox\BlockEditor\Support\BlockEditorLocale` whenever content depends on translations or dynamic feeds.

`BlockEditorLocale::resolveActive(?Request $request = null)` resolves in this order:

1. `lang` query parameter
2. `lang` request input
3. `app()->getLocale()`
4. `config('app.locale')`

Short locales are mapped to translation variants when possible:

- `de` may resolve to `de_DE`
- `en` may resolve to `en_US`

When `moox/localization` is installed, locale variants are read from the `localizations` table.  
`BlockEditorLocale::localeCandidates()` builds the locale list used by entity queries and feed mappers.

**Always pass the same locale to the Blade component that you used to load the translation record.**

Example in a controller:

```php
use Moox\BlockEditor\Support\BlockEditorLocale;

public function show(Request $request, string $slug): View
{
    $locale = BlockEditorLocale::resolveActive($request);

    $translation = // ... load published translation for $locale

    return view('pages.show', [
        'content' => $translation->content,
        'locale' => $locale,
    ]);
}
```

Language switch links should include `?lang=de_DE` (or your variant) so dynamic feeds and translated content stay in sync.

### 8.5 Reference integration: `moox/page` + `moox/frontend`

The canonical public-page setup in Moox uses two packages:

| Package | Role |
|---------|------|
| `moox/page` | `PageController`, route `page.show` (`/pages/{slug}`), loads published page translations |
| `moox/frontend` | Layout `moox::layouts.base` and page template `moox::page.default` |

Setup:

```bash
composer require moox/page moox/frontend
php artisan migrate
```

`Moox\Page\Http\Controllers\PageController` resolves the locale via `BlockEditorLocale::resolveActive($request)`, loads the published translation, and passes `content` and `locale` to the frontend template.

The default frontend template (`packages/frontend/resources/views/page/default.blade.php`) renders block content when `moox/block-editor` is installed:

```blade
@if (class_exists(\Moox\BlockEditor\Rendering\BlockContentRenderer::class))
    <x-moox-editor::block-content
        :content="$content"
        :locale="$locale ?? app()->getLocale()"
    />
@endif
```

You do not need to copy this template unless you want a custom layout. For custom themes, keep the `block-content` component and pass `content` + `locale` explicitly.

### 8.6 Step-by-step: render block JSON in your own view

Use this when you are **not** using `moox/page`, but still want to output editor content on a public route.

#### Step 1 — Store block JSON on your model

Add a `content` column (`TEXT`, `LONGTEXT`, or `JSON`) or use an existing translation field.

Optional model cast:

```php
protected function casts(): array
{
    return [
        'content' => 'array',
    ];
}
```

#### Step 2 — Resolve locale in the controller

```php
use Moox\BlockEditor\Support\BlockEditorLocale;

$locale = BlockEditorLocale::resolveActive($request);
```

Load the translation or record for that locale before rendering.

#### Step 3 — Pass data to the view

```php
return view('articles.show', [
    'article' => $article,
    'content' => $article->translate($locale)?->content ?? [],
    'locale' => $locale,
]);
```

#### Step 4 — Render in Blade

```blade
<article>
    <h1>{{ $article->title }}</h1>

    <x-moox-editor::block-content :content="$content" :locale="$locale" />
</article>
```

#### Step 5 — Verify output

Check that:

- paragraphs and headings render
- `dynamicFeed` blocks render when the source is registered (section 9)
- `?lang=` switches both static translation content and feed output

For debugging, you can render directly in a route or tinker:

```php
app(\Moox\BlockEditor\Rendering\BlockContentRenderer::class)->render($content, 'de_DE');
```

### 8.7 Supported block types (server-side)

The package ships these PHP renderers (registered in `BlockEditorServiceProvider`):

| Block type | Renderer | Output |
|------------|----------|--------|
| `paragraph` | `ParagraphBlockRenderer` | `<div>` with stored HTML content |
| `heading1` … `heading6` | `HeadingBlockRenderer` | `<h1>` … `<h6>` with escaped text |
| `dynamicFeed` | `DynamicFeedBlockRenderer` | Blade view from registered source |

The editor supports many more block types (images, tables, tabs, columns, …). Those are **not** rendered server-side yet unless you add a custom renderer.

Current behavior for unregistered types:

- if the block has `children`, children are rendered recursively
- otherwise the block is skipped (empty output, no fatal error)

Plan server-side renderers before relying on complex layouts in public pages.

### 8.8 Custom block renderers

Implement `Moox\BlockEditor\Rendering\Contracts\BlockRenderer`:

```php
use Moox\BlockEditor\Rendering\Contracts\BlockRenderer;
use Moox\BlockEditor\Rendering\RenderContext;

final class ImageBlockRenderer implements BlockRenderer
{
    public function supports(string $type): bool
    {
        return $type === 'image';
    }

    public function render(array $block, RenderContext $context): string
    {
        $url = (string) ($block['url'] ?? '');

        if ($url === '') {
            return '';
        }

        $alt = e((string) ($block['alt'] ?? ''));

        return '<img src="'.e($url).'" alt="'.$alt.'" loading="lazy">';
    }
}
```

Register the renderer by rebinding `BlockContentRenderer` in your application service provider and including your class in the renderer list (same pattern as `BlockEditorServiceProvider::bootingPackage()`).

Renderers receive:

- `$block` — the block JSON array
- `$context->locale` — resolved locale string

Return an HTML string. Return `''` to skip output gracefully.

### 8.9 Rendering flow

```
Blade <x-moox-editor::block-content>
  └─ BlockContentRenderer::render($content, $locale)
       ├─ normalizeContent() — JSON string or array → block list
       ├─ RenderContext($locale)
       └─ for each block:
            ├─ find BlockRenderer where supports(type)
            ├─ render HTML fragment
            └─ or recurse into children / skip
```

`DynamicFeedBlockRenderer` additionally:

1. resolves `sourceKey` from `EntityQuerySourceRegistry`
2. builds `EntityQueryDefinition::fromBlock(...)`
3. executes the source query with locale-aware filters
4. renders the configured Blade view with `$items`, `$block`, `$locale`

See section 9 for dynamic feed details.

### 8.10 Failure behavior

Public rendering is defensive:

- invalid JSON → empty output
- unknown block type without children → skipped
- unknown/disabled dynamic feed source → skipped, warning logged
- missing feed view or mapper → skipped, warning logged

The surrounding page keeps rendering; only the affected block is omitted.

### 8.11 Best practices

- Pass `locale` explicitly in public templates — do not rely on `app()->getLocale()` alone when translations use variants like `de_DE`.
- Use the same locale in the controller, translation query, and `block-content` component.
- Add `?lang=` to language switchers for dynamic feeds and translated routes.
- Cast or validate `content` in the model; the renderer does not run editor validation rules.
- For complex block types (tables, columns, media), add dedicated `BlockRenderer` classes before going live.
- Keep paragraph HTML trusted — it is output without additional escaping in `ParagraphBlockRenderer`.
- Heading text is stripped and escaped in `HeadingBlockRenderer`; do not expect inline markup in headings on the public site.

---

## 9. Dynamic Feed Component

The package also includes a `dynamicFeed` block type for rendering runtime-driven entity lists inside block content. Unlike static content blocks, this block does not persist rendered HTML or a fixed item list. Instead, it stores a small query definition such as the selected source, filters, limit, and view key. The actual data is resolved when the block is rendered.

This makes the block a good fit for content like:

- latest news
- filtered article lists
- category-specific teasers
- locale-aware content feeds

### 9.1 What the editor stores

The editor stores only the block configuration, for example:

```json
{
  "id": "feed-1",
  "type": "dynamicFeed",
  "sourceKey": "news",
  "limit": 5,
  "orderBy": "published_at",
  "orderDirection": "desc",
  "filters": {
    "category_id": 12
  },
  "view": "card"
}
```

At render time, the package:

1. resolves the registered source by `sourceKey`
2. builds an `EntityQueryDefinition`
3. applies filters and sorting
4. fetches the matching models
5. maps each model into a feed item payload
6. renders the configured Blade view

### 9.2 Reference implementation: block-editor package config

Dynamic feed configuration lives entirely in the block-editor package:

| Piece | Location |
|-------|----------|
| Global defaults | `config/moox-editor.php` → `dynamic_feed.mapping_defaults` |
| Source definitions | `config/dynamic-feed-sources.php` → merged into `dynamic_feed.sources` |
| Registration | Automatic via `DynamicFeedSourceRegistrar` in `BlockEditorServiceProvider` |
| Feed item mapper | `DraftFeedItemMapper` (default) or custom `FeedItemMapper` class |
| Blade views | Theme package views referenced in `views.*.view` |

Two keys matter and must not be confused:

- **`news`** — the dynamic feed **source key**. Array key under `dynamic_feed.sources`, persisted in block JSON as `sourceKey`.
- **`feed_item_mapping.relations`** — explicit relation config per source (`taxonomy`, `translation_relation`, `attribute`).

### 9.3 Step-by-step: add or extend a dynamic feed source

Follow these steps when you want to expose a new entity list in the block editor.

#### Step 1 — Add the source definition to `config/dynamic-feed-sources.php`

Add an entry under `dynamic_feed.sources` (file: `packages/block-editor/config/dynamic-feed-sources.php`). Global `mapping_defaults` provide translation field defaults; per-source relations are defined explicitly:

```php
use Moox\BlockEditor\EntityQuery\Mappers\DraftFeedItemMapper;
use Moox\News\Models\News;

// inside config/dynamic-feed-sources.php

return [
    'news' => [
        'enabled' => true,
        'model' => News::class,
        'label' => 'trans//news::news.news',
        'default_view' => 'card',
        'views' => [
            'card' => [
                'label' => 'Karten',
                'view' => 'myheco::news.blocks.dynamic-feed.card',
            ],
            'list' => [
                'label' => 'Liste',
                'view' => 'myheco::news.blocks.dynamic-feed.list',
            ],
        ],
        'filter_schema' => [
            'category_id' => [
                'type' => 'select',
                'label' => 'Kategorie',
                'nullable' => true,
                'apply' => 'taxonomy:category',
                'options_resolver' => 'category',
            ],
        ],
        'sortable_columns' => [
            'published_at' => 'nt.published_at',
            'title' => 'nt.title',
        ],
        'feed_item_mapper' => DraftFeedItemMapper::class,
        'feed_item_mapping' => [
            'untitled_label' => 'trans//news::news.untitled',
            'relations' => [
                'category' => [
                    'type' => 'taxonomy',
                    'output' => 'categories',
                    'label_attribute' => 'title',
                    'eager_load' => 'category.translations',
                ],
                'author' => [
                    'type' => 'translation_relation',
                    'output' => 'author_name',
                    'attributes' => ['name', 'title'],
                    'eager_load' => 'translations.author',
                ],
                'image' => [
                    'type' => 'attribute',
                    'path' => 'image',
                    'output' => 'image',
                    'resolve_url' => true,
                ],
            ],
        ],
    ],
];
```

`eager_load` paths are derived from `relations.*.eager_load` automatically — no separate root `eager_load` array is required.

Legacy flat keys (`taxonomy`, `author_relation`, `image_attribute`) are still normalized internally but deprecated.

#### Step 2 — Customize mapping (optional)

The default `DraftFeedItemMapper` works for most draft-based Moox entities. Configure field output via `feed_item_mapping.relations` in `config/dynamic-feed-sources.php` (see the `news` source for a full example).

Only implement a custom `FeedItemMapper` when relations-based mapping is not sufficient:

```php
namespace App\BlockEditor;

use Illuminate\Database\Eloquent\Model;
use Moox\BlockEditor\EntityQuery\Contracts\FeedItemMapper;

final class CustomFeedItemMapper implements FeedItemMapper
{
    public function map(Model $model, string $locale): array
    {
        // Return normalized feed item payload for Blade views.
        return [];
    }
}
```

Register the fully qualified class name in your source config:

```php
'feed_item_mapper' => CustomFeedItemMapper::class,
```

#### Step 4 — Create Blade views for each view variant

Each entry in `views` points to a Blade template. The news package ships two variants:

- `news::blocks.dynamic-feed.card` → `resources/views/blocks/dynamic-feed/card.blade.php`
- `news::blocks.dynamic-feed.list` → `resources/views/blocks/dynamic-feed/list.blade.php`

Every view receives three variables:

- `$items` — mapped feed item arrays from your mapper
- `$block` — the block JSON configuration
- `$locale` — active locale string

Start by copying the news views and adapt markup to your design system.

#### Step 5 — Verify in the editor

After registration, the source appears in the editor when the Filament field renders (embedded `data-dynamic-feed-sources`). In the block editor:

1. Insert a **Dynamic Feed** block.
2. Select your source (label from `label` in config).
3. Choose filters, limit, and view variant.
4. Preview the rendered output.

If the source does not appear, check that `enabled` is `true`, the service provider boots, and `moox/block-editor` is installed.

### 9.4 Extending an existing source

Once a source like `news` is registered, you can extend it without changing the source key.

#### Add a new view variant

Add a new key under `views` and create the matching Blade file:

```php
'views' => [
    'card' => [
        'label' => 'Karten',
        'view' => 'news::blocks.dynamic-feed.card',
    ],
    'list' => [
        'label' => 'Liste',
        'view' => 'news::blocks.dynamic-feed.list',
    ],
    'compact' => [
        'label' => 'Kompakt',
        'view' => 'news::blocks.dynamic-feed.compact',
    ],
],
```

The array key (`compact`) is persisted in block JSON as `view`. Existing blocks keep their stored view key.

#### Add a new filter

Add a filter key under `filter_schema`. The key becomes `block.filters.{key}` in JSON:

```php
'filter_schema' => [
    'category_id' => [
        'type' => 'select',
        'label' => 'Kategorie',
        'nullable' => true,
        'apply' => 'taxonomy:category',
        'options_resolver' => 'category',
    ],
    'type' => [
        'type' => 'select',
        'label' => 'Typ',
        'nullable' => true,
        'apply' => 'column:type',
        'options_resolver' => '',
    ],
],
```

Supported `apply` conventions:

- `taxonomy:{relation}` — filters via `whereHas` on a taxonomy relation
- `column:{column}` — filters on a parent-table column

For `options_resolver`, the block editor currently ships `category` out of the box. Other resolvers return an empty option list until you extend `FilterOptionsResolver` in `moox/block-editor`.

#### Add sortable columns

Expose logical sort keys editors or prefilled blocks can use:

```php
'sortable_columns' => [
    'published_at' => 'nt.published_at',
    'title' => 'nt.title',
    'created_at' => 'news.created_at',
],
```

The left side is the logical key stored in `orderBy`. The right side is the SQL column used in `orderBy(...)`. Use `nt.*` for translation-table columns because the query builder joins translations as `nt`.

#### Temporarily disable a source

Set `enabled` to `false`. The source disappears from the embedded catalog and existing blocks referencing it render nothing (with a logged warning).

### 9.4.1 Copy-paste snippets

**Config** (`packages/block-editor/config/dynamic-feed-sources.php`):

```php
'news' => [
    'enabled' => true,
    'model' => News::class,
    // ...
    'feed_item_mapping' => [
        'untitled_label' => 'trans//news::news.untitled',
        'relations' => [
            'category' => [
                'type' => 'taxonomy',
                'output' => 'categories',
                'label_attribute' => 'title',
                'eager_load' => 'category.translations',
            ],
            'author' => [
                'type' => 'translation_relation',
                'output' => 'author_name',
                'attributes' => ['name', 'title'],
                'eager_load' => 'translations.author',
            ],
            'image' => [
                'type' => 'attribute',
                'path' => 'image',
                'output' => 'image',
                'resolve_url' => true,
            ],
        ],
    ],
],
```

Sources are registered automatically at boot via `DynamicFeedSourceRegistrar` — no service provider registration is required.

**Custom mapper** (optional, when `DraftFeedItemMapper` + `relations` is not sufficient):

```php
final class CustomFeedItemMapper implements FeedItemMapper
{
    public function map(Model $model, string $locale): array
    {
        // Return normalized feed item payload.
        return [];
    }
}
```

**Blade views** (example paths in a theme package):

- `packages/myheco/resources/views/news/blocks/dynamic-feed/card.blade.php`
- `packages/myheco/resources/views/news/blocks/dynamic-feed/list.blade.php`

**Persisted block JSON** (with the `news` source key):

```json
{
  "type": "dynamicFeed",
  "sourceKey": "news",
  "limit": 5,
  "orderBy": "published_at",
  "orderDirection": "desc",
  "filters": {
    "category_id": 12
  },
  "view": "card"
}
```

### 9.5 Full configuration reference

Below is a detailed explanation of every supported source configuration key.

#### `enabled`

Type: `bool`  
Default: `true`

Controls whether the source is available at runtime.

- If `true`, the source can be resolved and rendered normally.
- If `false`, the source is treated as unavailable.
- `EntityQuerySourceRegistry::has()` only returns `true` for enabled sources.
- A block referencing a disabled or unknown source is skipped gracefully during rendering.

Use this when you want to keep source definitions in code but temporarily hide them from editors or disable output without removing the registration code.

#### `model`

Type: `class-string<\Illuminate\Database\Eloquent\Model>`

Defines the Eloquent model used as the query base.

Requirements and expectations:

- The class must exist.
- It must be an Eloquent model.
- The package expects a `translations()` relation.
- The query builder assumes a translated content model with draft/published semantics.

Internally, the package:

- starts from `Model::query()`
- joins the translations table with the alias `nt`
- filters to active and published records
- resolves locale-aware translation candidates

If the model is missing or invalid, the package logs a warning and returns an empty result.

#### `label`

Type: `string`  
Fallback: source key

Defines the human-readable label shown in the editor when the source is listed.

Supported forms:

- plain string, for example `'News'`
- translated string reference using the `trans//` prefix, for example `'trans//news::news.news'`

When the value starts with `trans//`, the prefix is removed and the remaining translation key is passed to Laravel's `__()` helper.

Examples:

- `'label' => 'News'`
- `'label' => 'trans//news::news.news'`

#### `default_view`

Type: `string`  
Fallback: first key of `views`

Defines which view variant should be used when the block itself does not explicitly choose a view.

This value is important in two places:

- the editor can use it as the initial default
- the server-side renderer falls back to it if the block has no `view`

Best practice:

- always set `default_view` explicitly
- make sure the referenced key exists in `views`

If `default_view` is missing, the first registered `views` key is used.

#### `views`

Type: `array<string, array{label?: string, view?: string}>`

Defines the available output variants for the source. Each array key is the stable internal view key stored in block JSON, while each value describes how the option appears in the editor and which Blade view renders it.

Example:

```php
'views' => [
    'card' => [
        'label' => 'Karten',
        'view' => 'news::blocks.dynamic-feed.card',
    ],
    'list' => [
        'label' => 'Liste',
        'view' => 'news::blocks.dynamic-feed.list',
    ],
],
```

Each view entry supports:

- `label`: the editor-facing name
- `view`: the Blade view name used during server-side rendering

Important notes:

- the array key such as `card` or `list` is the persisted value
- the Blade view must exist and be renderable
- if the selected view key does not exist, rendering is skipped gracefully and a warning is logged

#### `filter_schema`

Type: `array<string, array<string, mixed>>`

Defines which filters are shown in the editor and how they are applied to the query.

Each top-level key is the filter key stored in `block.filters`. In your example, that key is `category_id`.

Example:

```php
'filter_schema' => [
    'category_id' => [
        'type' => 'select',
        'label' => 'Kategorie',
        'nullable' => true,
        'apply' => 'taxonomy:category',
        'options_resolver' => 'category',
    ],
],
```

Common fields inside one filter definition:

- `type`
  Currently the editor UI is built around select-style filters. Use a value that matches your UI intent, typically `select`.
- `label`
  Human-readable label shown in the editor.
- `nullable`
  If `true`, the editor shows an "all" style empty option. If `false`, the empty option becomes a required "please choose" prompt.
- `apply`
  Defines how the selected value affects the Eloquent query.
- `options_resolver`
  Defines how the selectable options are loaded for the editor.

##### `apply` conventions

The package currently supports convention-based filter application:

- `taxonomy:{relation}`
  Applies `whereHas('{relation}', fn ($query) => $query->whereKey($value))`
- `column:{column}`
  Applies a direct column comparison on the parent table

Examples:

- `'apply' => 'taxonomy:category'`
- `'apply' => 'column:status'`

If `apply` is missing or empty, the filter is visible in the schema but does not affect the query.

##### `options_resolver`

`options_resolver` is used when the Filament field renders: `DynamicFeedEditorCatalog` resolves options via `FilterOptionsResolver` and embeds them in `data-dynamic-feed-sources`.

In your example:

```php
'options_resolver' => 'category'
```

This means category options are resolved server-side for that filter. If no resolver is defined, the embedded catalog provides an empty option list.

#### `sortable_columns`

Type: `array<string, string>`

Maps editor-facing sort keys to actual SQL columns.

Example:

```php
'sortable_columns' => [
    'published_at' => 'nt.published_at',
    'title' => 'nt.title',
],
```

How it works:

- the block stores a logical key such as `published_at`
- the query builder resolves that key through this map
- the resulting SQL column is used in `orderBy(...)`

Important details:

- if the requested sort key is unknown, the query falls back to `published_at` when that mapping exists
- if no valid mapped column is found, no explicit sorting is applied
- using `nt.*` is common because the builder joins the translations table using the alias `nt`

Best practice:

- expose only safe, expected sort keys
- always provide a `published_at` mapping when sorting by publication date is relevant

#### `feed_item_mapper`

Type: `class-string<\Moox\BlockEditor\EntityQuery\Contracts\FeedItemMapper>`

Defines the mapper that transforms each Eloquent model into the array structure expected by your Blade views.

Contract:

```php
interface FeedItemMapper
{
    public function map(Model $model, string $locale): array;
}
```

Why this exists:

- keeps rendering views simple
- decouples query models from presentation payloads
- allows package- or domain-specific output formatting

A mapper usually normalizes things like:

- translated title
- teaser or excerpt
- URL or route
- image path
- publication date
- taxonomy metadata

If the mapper class is missing, invalid, or does not implement the required interface, the package logs a warning and returns an empty collection.

### 9.6 Global dynamic feed configuration

In addition to per-source registration, the package also ships global defaults in `config/moox-editor.php`:

```php
'dynamic_feed' => [
    'max_limit' => 50,
    'default_limit' => 5,
    'default_order_by' => 'published_at',
    'default_order_direction' => 'desc',
],
```

These values affect how block data is normalized into an `EntityQueryDefinition`.

#### `max_limit`

The hard upper bound for requested item count. Even if an editor enters a higher number, the package clamps the final limit to this maximum.

#### `default_limit`

Fallback item count when a block does not define `limit`.

#### `default_order_by`

Fallback logical sort key when a block does not define `orderBy`.

This key should normally exist in `sortable_columns`, otherwise no effective sorting may be applied.

#### `default_order_direction`

Fallback sort direction when a block does not define `orderDirection`.

Only `asc` is treated as ascending. Every other value falls back to `desc`.

### 9.7 Editor behavior

When the editor loads the dynamic feed UI, it reads the embedded catalog (`data-dynamic-feed-sources`) which includes:

- registered source keys
- source labels
- available views
- filter schema
- default view

In the editor UI, users can currently configure:

- source
- filter values
- item limit
- view variant

The block structure also contains `orderBy` and `orderDirection`, and these values are respected by the backend query definition. If you expose sorting in your UI or prefill it programmatically, the registered `sortable_columns` map is used to translate logical keys into SQL columns.

### 9.8 Rendering flow

Server-side rendering is handled by `DynamicFeedBlockRenderer`.

The renderer:

1. checks whether the `sourceKey` exists and is enabled
2. resolves the source from the registry
3. determines the effective view key from `block.view` or `default_view`
4. builds the query definition from the block payload plus locale
5. executes the query through the registered source
6. renders the configured Blade view with:
   - `items`
   - `block`
   - `locale`

Your Blade view therefore receives already mapped feed items and can focus on presentation.

### 9.9 Practical notes and best practices

- Define sources in `packages/block-editor/config/dynamic-feed-sources.php`. Registration happens automatically at boot.
- Keep source keys stable because they are persisted in block JSON.
- Always provide at least one valid `views` entry.
- Always provide a valid `feed_item_mapper` for non-trivial output.
- Make sure your model and mapper are locale-aware when working with translated entities.
- Prefer explicit `default_view` values instead of relying on the first array key.
- Keep filter keys stable once content has already been created with them.
- Treat `sortable_columns` as a whitelist. Do not pass raw user-controlled column names into queries.

### 9.10 Example explained line by line

Using the `config/moox-editor.php` news source as reference:

- `'news'` (source key)
  The array key under `dynamic_feed.sources` and the value persisted in block JSON as `sourceKey`.
- `'dynamic_feed.sources'` (config path)
  Holds all source definitions in `config/moox-editor.php`. This is **not** the `sourceKey`.
- `'enabled' => true`
  The source is active and can be used by editors and renderers.
- `'model' => News::class`
  The query starts from the `News` model.
- `'label' => 'trans//news::news.news'`
  The editor label is translated through Laravel's translation system.
- `'default_view' => 'card'`
  The `card` variant is used unless the block chooses another view.
- `'views'`
  Defines the available output variants and their Blade views.
- `'filter_schema'`
  Adds editor filters. Here, `category_id` is shown as a selectable category filter.
- `'apply' => 'taxonomy:category'`
  The chosen category value is applied through a taxonomy relation filter.
- `'options_resolver' => 'category'`
  The editor asks the backend for selectable category options.
- `'sortable_columns'`
  Allows logical sort keys such as `published_at` or `title` to map to real SQL columns.
- `'feed_item_mapper' => DraftFeedItemMapper::class`
  Each model is transformed into the payload consumed by the selected Blade view (via `feed_item_mapping.relations`).

### 9.11 Failure behavior

The package is intentionally defensive. If a dynamic feed is misconfigured, it usually fails softly instead of breaking the whole page.

Examples:

- unknown or disabled source: block renders nothing
- missing or invalid model: query returns no items
- missing or invalid mapper: query returns no items
- unknown view key: block renders nothing

In these cases the package logs warnings so the issue can be diagnosed without causing a fatal frontend error.

