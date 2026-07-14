# Moox Page

CMS page entity for creating and managing pages with Filament, block editor, layouts, and translations.

## Features

- Multilingual pages with publish workflow
- Block editor content field
- Layout mapping to host Blade views
- Homepage handling with single-startpage constraint
- Frontend routes (`/` and `/{slug}`)
- Full-response caching for anonymous visitors
- Filament admin resource with taxonomy support

## Installation

```bash
composer require moox/page
php artisan moox:install
```

## Configuration

Publish and customize `config/page.php`:

- `models.*` — swap page models in host apps
- `layouts` — map `pages.layout` values to Blade views
- `content_renderer` — class implementing `PageContentRenderer`
- `cache.enabled`, `cache.ttl`, `cache.locale_ttl` — frontend caching

## Frontend

When `page.frontend.enabled` is `true`, the package registers:

- `GET /` — homepage or fallback index
- `GET /{slug}` — published page by slug or legacy permalink

Reserved slugs are configured in `page.reserved_slugs`.

## Commands

```bash
php artisan pages:normalize-permalinks
php artisan pages:export-seed-data
```

## Testing

```bash
php artisan test --compact
```

## License

MIT
