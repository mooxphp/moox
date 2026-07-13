# Moox Block Editor — integration templates

## 1. Filament resource (block JSON storage)

### Database / model

- Store `content` as JSON/`array` on the **translation** model (draft entities).
- Cast: `'content' => 'array'`
- Add `content` to `getCustomFillable()` / translated attributes.

### Resource form (Filament 4)

Use `Filament\Schemas\Schema` and `->components([...])` (not Filament 3 `Form`):

```php
use Filament\Schemas\Schema;
use Moox\BlockEditor\Forms\Components\BlockEditor;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        BlockEditor::make('content')
            ->label(__('core::core.content'))
            ->columnSpanFull(),
    ]);
}
```

Reference: `Moox\BlockEditor\Filament\Resources\TemplateResource`.

### Assets

```bash
php artisan vendor:publish --tag=moox-editor-assets
```

Verify URLs under `public/vendor/moox/block-editor/`.

### Optional field flags

```php
BlockEditor::make('content')
    ->positiveBlock(['paragraph', 'heading2', 'dynamicFeed'])
    ->negativeBlock(['embed'])
    ->templates(false)
    ->mediaLibraryCollection('pages');
```

## 2. Dynamic feed source (entity package)

### Config block (`packages/{pkg}/config/{pkg}.php`)

```php
'dynamic_feed' => [
    'enabled' => true,
    'model' => MainModel::class,
    'label' => 'trans//{pkg}::{pkg}.plural',
    'default_view' => 'card',
    'views' => [
        'card' => [
            'label' => 'Karten',
            'view' => '{pkg}::blocks.dynamic-feed.card',
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
    'feed_item_mapper' => 'Moox\\{Pkg}\\BlockEditor\\{Pkg}FeedItemMapper',
],
```

### ServiceProvider

```php
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;

public function packageBooted(): void
{
    if (class_exists(EntityQuerySourceRegistry::class)) {
        EntityQuerySourceRegistry::register('{key}', config('{pkg}.dynamic_feed', []));
    }
}
```

### FeedItemMapper

- Implements `Moox\BlockEditor\EntityQuery\Contracts\FeedItemMapper`
- Maps Eloquent model → plain array for Blade (use `strip_tags` for teasers in mapper or view)
- **No query logic** in mapper — queries live in `EntityQueryBuilder` + config `apply` rules

### Blade views

Place under `resources/views/blocks/dynamic-feed/`. Use `{{ }}` only for user-facing strings from mapped data; never `{!! !!}` for query fields.

## 3. Public rendering

### Blade component (any template)

```blade
<x-moox-editor::block-content
    :content="$translation->content ?? []"
    :locale="$locale"
/>
```

`$locale` should come from `BlockEditorLocale::resolveActive($request)` in the controller.

### Generic page shell (`moox/frontend`)

View `moox::page.default` expects:

| Variable | Description |
| --- | --- |
| `$title` | Page title (optional) |
| `$description` | Lead HTML (optional) |
| `$content` | Block JSON array or legacy HTML |
| `$locale` | Active locale string |
| `$contentWidth` | Tailwind width class (e.g. `max-w-4xl`) |

Requires `moox/frontend` ServiceProvider (registers `moox` view namespace).

## 4. Page frontend

`moox/page` ships a public route when `page.frontend.enabled` is true:

- Route name: `page.show`
- URI: `{prefix}/{slug}` (default `pages/{slug}`)
- Controller: `Moox\Page\Http\Controllers\PageController`
- Template: `moox::page.default` via `PageFrontend`

Config (`config/page.php`):

```php
'frontend' => [
    'enabled' => true,
    'prefix' => 'pages',
    'middleware' => ['web'],
],
```

Dependencies: `moox/block-editor`, `moox/frontend`.

### Verification checklist

- [ ] Published page with block JSON returns 200 on `route('page.show', ['slug' => '…'])`
- [ ] Paragraph/heading blocks render HTML
- [ ] `dynamicFeed` block resolves registered source (e.g. news)
- [ ] Draft/unpublished translation returns 404
- [ ] `?lang=de` switches locale for feed + block rendering

## 5. Editor block JSON (`dynamicFeed`)

Stored shape (no entity rows in JSON):

```json
{
  "type": "dynamicFeed",
  "sourceKey": "news",
  "limit": 5,
  "orderBy": "published_at",
  "orderDirection": "desc",
  "filters": { "category_id": 12 },
  "view": "card",
  "emptyMessage": ""
}
```

Editor reads embedded `data-dynamic-feed-sources` from `DynamicFeedEditorCatalog` (no `moox-editor.dynamic-feeds.*` routes).
