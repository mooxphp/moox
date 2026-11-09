## Media API

This API serves media assets (images/videos/files) for clients such as `moox/editor`.

## Base URL, versioning, middleware

- **Default base URL**: `/api/media`
- **Configuration**: `packages/media/config/media.php` → `api.*`
  - **`api.prefix`** (default: `api/media`)
  - **`api.version`** (default: empty → no version segment)
  - **`api.middleware`** (default: `['web', 'auth', 'throttle:60,1']`)

### Auth note (important)

By default the API uses `web` + `auth` (session / auth cookie). That means:

- In the **browser (logged in)**, requests work directly.
- From a **JS client** on the same domain, requests work when cookies are sent.
- For **server-to-server** or external clients you would need a different auth setup (e.g. token/Bearer) and adjust the middleware accordingly.

## Endpoints

### 1) GET `/api/media`

Lists media items. The response is **paginated** (default: **25** per page).

#### Query parameters

- **`lang`** (string, optional)
  - Forces the language for this request (override).
  - If `lang` is not set, the API tries to read `lang` from the `Referer` (e.g. when the admin URL contains `?lang=...`); otherwise `app()->getLocale()`.
- **`search`** (string, optional)
  - Searches `file_name` and the translated fields `name`, `title`, `alt`.
- **`collection`** (int, optional)
  - Filters by **`media_collection_id`** (e.g. `collection=1`)
- **`type`** (string, optional)
  - `image` → `mime_type LIKE image/%`
  - `video` → `mime_type LIKE video/%`
  - `document` → `mime_type LIKE (application/% OR text/% OR model/%)`
- **`per_page`** (int, optional) — 1..100, default 25
- **`page`** (int, optional)

#### Examples

```http
GET /api/media
```

```http
GET /api/media?type=image
```

```http
GET /api/media?type=document
```

```http
GET /api/media?search=test&type=image
```

```http
GET /api/media?collection=1
```

```http
GET /api/media?lang=de_DE&type=image
```

#### Response (schema)

Standard Laravel pagination (resource collection) with:

- **`data`**: array of media items
- **`links`** / **`meta`**: pagination metadata
- **`context.locale`**: the effective locale used for translations

##### `data[]` item fields

- **`id`** (int): primary key
- **`url`** (string): **original** public asset URL (Spatie `getUrl()`). Intended for admin/editor tooling only.
- **`thumbnail_url`** (string|null): thumbnail conversion (if available)
- **`preview_url`** (string|null): preview conversion (if available)
- **`poster_url`** (string|null): for videos (fallback: preview → thumbnail)
- **`file_name`** (string)
- **`mime_type`** (string|null)
- **`type`** (`image`|`video`|`document`|`other`): derived from `mime_type`
- **`name`** (string|null)
- **`title`** (string|null)
- **`alt`** (string|null)
- **`picture`** (object): web delivery payload for `<picture>` (see below)
- **`delivery`** (object): profile payloads
  - **`web`**: same shape as `picture`
  - **`mail`**: `{ "url": "…/mail…" }` (single raster; SVG → `mail-png`)
  - **`customer_api`** (optional): `{ "url": "…/customer_api…" }` — only present when `media.delivery.profiles.customer_api.enabled` is true; URL may be `null` while queued or when the asset is skipped (e.g. `static_path` / SVG)
- **`variants`** (array): conversion catalog (admin/debug; may include original)
- **`collection`** (object|null): `{ id, name }`
- **`created_at`** (string|null)
- **`updated_at`** (string|null)

**When using `customer_api` for external consumers:** do not use top-level `url` or `variants[].url` for originals. Use only `delivery.customer_api.url`. If that field is missing or `null`, do **not** fall back to `url`.

##### `picture` / `delivery.web`

```json
{
  "profile": "web",
  "mode": "picture",
  "alt": "...",
  "sizes": null,
  "sources": [
    { "type": "image/avif", "srcset": "…/web-480-avif.avif 480w, … 960w, … 1440w" },
    { "type": "image/webp", "srcset": "…" }
  ],
  "img": {
    "src": "…/medium_large…",
    "srcset": null,
    "loading": "lazy",
    "decoding": "async"
  }
}
```

Missing conversions are omitted (`sources` may be empty while conversions are still queued). Web srcsets start at `min_responsive_width` (default 480).

#### Error codes

- **401/302**: not logged in (redirect vs JSON depending on Accept / request context)
- **403**: policy denies access (`viewAny` may allow, but middleware can still block)
- **422**: validation error (e.g. invalid `type`)

### 2) GET `/api/media/{id}`

Single media item (including `picture`) — e.g. selection for static pages / editors.

#### Query parameters

- **`lang`** (string, optional) — same as list

#### Response

- **200**: `{ data, context }` — `data` uses the same item schema as the list
- **401/302 / 403 / 404**: auth / policy / not found

### 3) POST `/api/media`

Upload files into the media library.

#### Request (multipart/form-data)

- **`file`**: uploaded file
- **`media_collection_id`** (int): target collection ID
- **`lang`** (string): e.g. `en_US`, `de_DE`
- **`name`** (string): translated display name (default: original file name)
- **`title`** (string): default: file name without extension
- **`alt`** (string): default: file name without extension

#### Response

- **201**: upload successful
  - `{ message, data, context }`
  - `data` is a single media item in the same schema as GET
- **409**: duplicate (same file already exists)
  - `{ message, existing_id, context }`
- **422**: validation error
