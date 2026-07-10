# Moox Editor Template API

This document describes the Template API of the `moox/block-editor` package, based on the current implementation in `routes/api.php`, `TemplateController`, and the Form Requests.

## Base URL and Configuration

Routes are registered under:

- `{prefix}/{version}/templates`

Configuration in `config/moox-editor.php`:

```php
return [
    'api' => [
        'prefix' => 'api/editor',
        'version' => 'v1',
        'middleware' => ['web', 'auth', 'throttle:60,1'],
        'authorization' => null,
    ],
];
```

Examples:

- `prefix=api/editor`, `version=v1` → `/api/editor/v1/templates`
- `prefix=api/editor`, `version=''` → `/api/editor/templates`

### Named routes

All routes use the `moox-editor.` name prefix:

| Method | Path | Route name |
|--------|------|------------|
| `GET` | `/templates` | `moox-editor.templates.index` |
| `POST` | `/templates` | `moox-editor.templates.store` |
| `PUT`/`PATCH` | `/templates/{template}` | `moox-editor.templates.update` |
| `DELETE` | `/templates/{template}` | `moox-editor.templates.destroy` |

There is **no** `show` endpoint. Individual templates are loaded via the paginated index or from the create/update response.

## Endpoints

All endpoints are relative to `{prefix}/{version}/templates`:

- `GET /templates` — list (pagination, search, sort)
- `POST /templates` — create a template
- `PATCH /templates/{id}` — partial update
- `PUT /templates/{id}` — full or partial update
- `DELETE /templates/{id}` — delete

## Quickstart with cURL

The examples below use the default path `/api/editor/v1/templates`.
Adjust the URL if `prefix` or `version` were changed in config.

```bash
BASE_URL="https://your-app.test/api/editor/v1/templates"
```

### Authentication

With the default middleware (`web`, `auth`), requests must be made as an authenticated user:

- **Session auth**: send the session cookie (e.g. from the browser) and include the CSRF token header `X-CSRF-TOKEN` for mutating requests.
- **API/Bearer auth**: add `-H "Authorization: Bearer <token>"` when your app is configured for token-based auth.

The editor frontend sends `credentials: 'same-origin'`, `Accept: application/json`, and `X-CSRF-TOKEN` automatically.

### 1) Create a template

```bash
curl -X POST "$BASE_URL" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Landing Page",
    "slug": "landing-page",
    "content": [
      { "id": "block-1", "type": "paragraph", "content": "Hello World" }
    ]
  }'
```

### 2) List templates (with filter/sort)

```bash
curl -X GET "$BASE_URL?search=landing&per_page=20&sort=updated_at&direction=desc" \
  -H "Accept: application/json"
```

### 3) Update a template (PATCH)

```bash
curl -X PATCH "$BASE_URL/1" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Landing Page Updated"
  }'
```

### 4) Delete a template

```bash
curl -X DELETE "$BASE_URL/1" \
  -H "Accept: application/json"
```

## Authorization and Middleware

`authorization` is controlled by `Moox\BlockEditor\Support\ApiAuthorization::isEnabled()`:

- `authorization=true` — policies and Form Request `authorize()` are always enforced
- `authorization=false` — policies and Form Request `authorize()` are disabled
- `authorization=null` (default) — auto mode:
  - middleware list empty (`null` or `[]`) → authorization disabled
  - middleware list not empty → authorization enabled

Default setup:

- Middleware: `web`, `auth`, `throttle:60,1`
- `TemplatePolicy`: allows access for any authenticated user

Typical error responses:

- unauthenticated: `401` for JSON requests (or `302` redirect for non-JSON)
- unauthorized (policy denied): `403`
- validation errors: `422`

## Query parameters for `GET /templates`

Validated by `IndexTemplateRequest`:

- `per_page`: optional, `integer`, `1..100`, default in repository: `50`
- `search`: optional, `string`, max `100`
- `sort`: optional, one of `id`, `name`, `slug`, `created_at`, `updated_at`
- `direction`: optional, `asc` or `desc`

Repository defaults when omitted:

- `sort=id`
- `direction=desc`

Example:

```http
GET /api/editor/v1/templates?search=landing&per_page=20&sort=updated_at&direction=desc
Accept: application/json
```

`search` matches `name` and `slug` via `LIKE %term%`.

The editor frontend loads all templates by paginating the index with `per_page=100`, `sort=id`, and `direction=desc`.

## Request payloads

### `POST /templates`

Validated by `StoreTemplateRequest`:

- `name`: `required|string|max:255`
- `slug`: `nullable|string|max:255|unique`
- `content`: `nullable|array`

Example:

```json
{
  "name": "Landing Page",
  "slug": "landing-page",
  "content": [
    {
      "id": "block-1",
      "type": "paragraph",
      "content": "Hello World"
    }
  ]
}
```

### `PATCH` / `PUT /templates/{id}`

Validated by `UpdateTemplateRequest`:

- `name`: `sometimes|required|string|max:255`
- `slug`: `nullable|string|max:255|unique` (with `ignore(currentId)`)
- `content`: `nullable|array`

Example:

```json
{
  "name": "Landing Page Updated"
}
```

Or a content-only update:

```json
{
  "content": [
    {
      "id": "block-1",
      "type": "heading2",
      "content": "Title"
    }
  ]
}
```

## Response formats

### `GET /templates`

Laravel paginator JSON, for example:

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Landing Page",
      "slug": "landing-page",
      "content": [],
      "created_at": "2026-04-13T10:00:00.000000Z",
      "updated_at": "2026-04-13T10:00:00.000000Z"
    }
  ],
  "per_page": 50,
  "total": 1,
  "last_page": 1
}
```

### `POST /templates`

- Status: `201`
- Body: created template as JSON

### `PATCH` / `PUT /templates/{id}`

- Status: `200`
- Body: updated template (`fresh()`)

### `DELETE /templates/{id}`

- Status: `204 No Content`
- Body: empty

## Sanitizing and data model

On store/update, `content` is normalized and sanitized via `TemplateContentSanitizer`:

- `StoreTemplateRequest::passedValidation()`
- `UpdateTemplateRequest::passedValidation()`

Model `Moox\BlockEditor\Models\Template`:

- Table: `editor_templates`
- Fillable: `name`, `slug`, `content`
- Cast: `content` → `array`

## Editor frontend integration

The editor resolves the templates API URL in this order:

1. `window.mooxEditorTemplatesApiUrl`
2. `data-templates-api-url` on the editor root element
3. Fallback `/api/editor/v1/templates`

The Filament field sets `data-templates-api-url` to `route('moox-editor.templates.index')`, so the URL stays in sync when prefix or version change in config.

On `401` or `403`, the editor disables further API calls for the current session to avoid repeated failed requests.

## Dynamic feed (editor)

There is **no** HTTP API for dynamic feeds. Sources, filter schemas, views, and filter options are built server-side when the block editor field is rendered via `DynamicFeedEditorCatalog` and passed to the editor as `data-dynamic-feed-sources`.

### Config layers

| Layer | File | Purpose |
|-------|------|---------|
| Global defaults | `config/moox-editor.php` → `dynamic_feed` | `max_limit`, `default_limit`, `default_order_by`, `default_order_direction` |
| Source definition | Consumer config, e.g. `config/news.php` → `dynamic_feed` | `model`, `views`, `filter_schema`, `sortable_columns`, `feed_item_mapper`, … |

Publish global config: `php artisan vendor:publish --tag=moox-editor-config`

Sources are registered in consumer packages via `EntityQuerySourceRegistry::register(...)` (e.g. in `news`). See `docs/DEVELOPER.md` § 9 and `.cursor/skills/moox-block-editor/integration.md`.

## Frontend rendering (public pages)

Render block content server-side:

```blade
<x-moox-editor::block-content :content="$translation->content" :locale="app()->getLocale()" />
```

The `dynamicFeed` block type stores only query configuration (`sourceKey`, `limit`, `filters`, `view`, …). Data is loaded at runtime through registered entity sources.
