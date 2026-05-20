## Media API

Diese API liefert Media-Assets (Bilder/Videos/Dateien) für z.B. `moox/editor`

## Basis, Versionierung, Middleware

- **Default Base URL**: `/api/media`
- **Konfiguration**: `packages/media/config/media.php` → `api.*`
  - **`api.prefix`** (default: `api/media`)
  - **`api.version`** (default: leer → kein Versions-Segment)
  - **`api.middleware`** (default: `['web', 'auth', 'throttle:60,1']`)

### Auth-Hinweis (wichtig)

Standardmäßig läuft die API mit `web` + `auth` (Session/Auth-Cookie). Das heißt:

- Im **Browser (eingeloggt)** funktionieren Requests direkt.
- Von einem **JS-Client** auf derselben Domain funktionieren Requests, wenn Cookies mitgeschickt werden.
- Für **Server-zu-Server** oder externe Clients bräuchten wir dann ein anderes Auth-Setup (z.B. Token/Bearer) und wir müssen dann die Middleware entsprechend anpassen.

## Endpunkte

### 1) GET `/api/media`

Listet Media-Items. Die Antwort ist **paginiert** (Default: **25** pro Seite).

#### Query-Parameter

- **`lang`** (string, optional)
  - Erzwingt die Sprache für diesen Request (Override).
  - Wenn `lang` nicht gesetzt ist, versucht die API `lang` aus dem `Referer` zu lesen (z.B. wenn die Admin-URL `?lang=...` enthält); sonst `app()->getLocale()`.
- **`search`** (string, optional)
  - Sucht in `file_name` sowie in den übersetzten Feldern `name`, `title`, `alt`.
- **`collection`** (int, optional)
  - Filtert nach **`media_collection_id`** (z.B. `collection=1`)
- **`type`** (string, optional)
  - `image` → `mime_type LIKE image/%`
  - `video` → `mime_type LIKE video/%`
  - `document` → `mime_type LIKE (application/% OR text/% OR model/%)`
- **`per_page`** (int, optional) — 1..100, Default 25
- **`page`** (int, optional)

#### Beispiele

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

#### Response (Schema)

Die Antwort ist eine standard Laravel Pagination (Resource Collection) mit:

- **`data`**: Array von Media-Items
- **`links`** / **`meta`**: Pagination-Metadaten
- **`context.locale`**: die effektiv verwendete Locale für Übersetzungen

##### `data[]` Item-Felder

- **`id`** (int): Primary Key
- **`url`** (string): öffentliche Asset-URL (Spatie `getUrl()`)
- **`thumbnail_url`** (string|null): Thumbnail-Conversion (falls verfügbar)
- **`preview_url`** (string|null): Preview-Conversion (falls verfügbar)
- **`poster_url`** (string|null): für Videos (Fallback: preview → thumbnail)
- **`file_name`** (string)
- **`mime_type`** (string|null)
- **`type`** (`image`|`video`|`document`|`other`): abgeleitet aus `mime_type`
- **`name`** (string|null):
- **`title`** (string|null)
- **`alt`** (string|null)
- **`collection`** (object|null): `{ id, name }`
- **`created_at`** (string|null)
- **`updated_at`** (string|null)

#### Fehlercodes

- **401/302**: nicht eingeloggt (je nach Accept/Request-Kontext Redirect vs JSON)
- **403**: Policy verweigert Zugriff (aktuell `viewAny` erlaubt, aber Middleware kann blocken)
- **422**: Validation Fehler (z.B. ungültiges `type`)

### 2) POST `/api/media`

Upload von Files in die Mediathek

#### Request (multipart/form-data)

- **`file`** : Upload Datei
- **`media_collection_id`** (int): Ziel-Collection ID
- **`lang`** (string): z.B. en_US, de_DE
- **`name`** (string): Übersetztes Anzeigename-Feld (Default: Original-Dateiname)
- **`title`** (string): Default: Dateiname ohne Extension
- **`alt`** (string): Default: Dateiname ohne Extension

#### Response

- **201**: Upload erfolgreich
  - `{ message, data, context }`
  - `data` ist ein einzelnes Media-Item im selben Schema wie bei GET
- **409**: Duplicate (gleiche Datei schon vorhanden)
  - `{ message, existing_id, context }`
- **422**: Validation Fehler

