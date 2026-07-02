# Moox Editor Template API

Diese Datei beschreibt die Template-API des `moox/block-editor` Packages auf Basis des aktuellen Codes in `routes/api.php`, `TemplateController` und den Form Requests.

## Basis und Konfiguration

Die Route-Basis setzt sich so zusammen:

- `{prefix}/{version}/templates`

Konfiguration in `config/moox-editor.php`:

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

Beispiele:

- `prefix=api/editor`, `version=v1` -> `/api/editor/v1/templates`
- `prefix=api/editor`, `version=''` -> `/api/editor/templates`

## Endpunkte

Alle Endpunkte basieren auf `{prefix}/{version}/templates`:

- `GET /templates` - Liste (Pagination, Search, Sort)
- `POST /templates` - Template erstellen
- `GET /templates/{id}` - einzelnes Template laden
- `PATCH /templates/{id}` - teilweise aktualisieren
- `PUT /templates/{id}` - vollständig oder teilweise aktualisieren
- `DELETE /templates/{id}` - löschen

## Quickstart mit cURL

Die folgenden Beispiele verwenden den Standardpfad `/api/editor/v1/templates`.
Passe die URL an, wenn `prefix` oder `version` in der Config geaendert wurden.

```bash
BASE_URL="https://your-app.test/api/editor/v1/templates"
```

Hinweis zu Auth:

- Bei Session-Auth (Standard mit `web` + `auth`) musst du die Requests als eingeloggter User ausfuehren (z. B. mit Browser-Cookie).
- Bei API-Auth/Bearer-Setup kannst du z. B. `-H "Authorization: Bearer <token>"` ergaenzen.

### 1) Template erstellen

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

### 2) Templates auflisten (mit Filter/Sortierung)

```bash
curl -X GET "$BASE_URL?search=landing&per_page=20&sort=updated_at&direction=desc" \
  -H "Accept: application/json"
```

### 3) Ein Template laden

```bash
curl -X GET "$BASE_URL/1" \
  -H "Accept: application/json"
```

### 4) Template aktualisieren (PATCH)

```bash
curl -X PATCH "$BASE_URL/1" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Landing Page Updated"
  }'
```

### 5) Template loeschen

```bash
curl -X DELETE "$BASE_URL/1" \
  -H "Accept: application/json"
```

## Authorization und Middleware

`authorization` wird von `Moox\BlockEditor\Support\ApiAuthorization::isEnabled()` gesteuert:

- `authorization=true`: Policies/FormRequest-`authorize()` immer aktiv
- `authorization=false`: Policies/FormRequest-`authorize()` aus
- `authorization=null` (Default): Auto-Modus
  - Middleware-Liste leer (`null` oder `[]`) -> Authorization aus
  - Middleware-Liste nicht leer -> Authorization an

Standard-Setup:

- Middleware: `web`, `auth`, `throttle:60,1`
- Policy `TemplatePolicy`: erlaubt Zugriff fuer authentifizierte Benutzer

Typische Fehlercodes:

- unauthenticated: haeufig `302` (Redirect) oder `401` JSON (bei API-Requests mit JSON-Expectations)
- unauthorized (Policy verweigert): `403`
- Validation-Fehler: `422`

## Query-Parameter fuer `GET /templates`

Validierung aus `IndexTemplateRequest`:

- `per_page`: optional, `integer`, `1..100`, Default in Controller: `50`
- `search`: optional, `string`, max `100`
- `sort`: optional, einer von `id`, `name`, `slug`, `created_at`, `updated_at`
- `direction`: optional, `asc` oder `desc`

Controller-Defaults:

- `sort=id`
- `direction=desc`

Beispiel:

```http
GET /api/editor/v1/templates?search=landing&per_page=20&sort=updated_at&direction=desc
Accept: application/json
```

`search` sucht in `name` und `slug` per `LIKE %term%`.

## Request-Payloads

### `POST /templates`

Validierung aus `StoreTemplateRequest`:

- `name`: `required|string|max:255`
- `slug`: `nullable|string|max:255|unique`
- `content`: `nullable|array`

Beispiel:

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

### `PATCH`/`PUT /templates/{id}`

Validierung aus `UpdateTemplateRequest`:

- `name`: `sometimes|required|string|max:255`
- `slug`: `nullable|string|max:255|unique` (mit `ignore(currentId)`)
- `content`: `nullable|array`

Beispiel:

```json
{
  "name": "Landing Page Updated"
}
```

oder reines Content-Update:

```json
{
  "content": [
    {
      "id": "block-1",
      "type": "heading2",
      "content": "Titel"
    }
  ]
}
```

## Response-Formate

### `GET /templates`

Laravel-Paginator-JSON, z. B.:

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
- Body: erstelltes Template als JSON

### `GET /templates/{id}`

- Status: `200`
- Body: Template als JSON

### `PATCH`/`PUT /templates/{id}`

- Status: `200`
- Body: aktualisiertes Template (`fresh()`)

### `DELETE /templates/{id}`

- Status: `204 No Content`
- Body: leer

## Sanitizing und Datensicherheit

Beim Speichern/Update wird `content` ueber `TemplateContentSanitizer` normalisiert/sanitized:

- `StoreTemplateRequest::passedValidation()`
- `UpdateTemplateRequest::passedValidation()`

Model `Moox\BlockEditor\Models\Template`:

- Tabelle: `editor_templates`
- Fillable: `name`, `slug`, `content`
- Cast: `content` -> `array`

## Editor-Frontend Integration

Der Editor ermittelt die Templates-API URL in dieser Reihenfolge:

1. `data-templates-api-url` am Root
2. `window.mooxEditorTemplatesApiUrl`
3. Fallback `/api/editor/v1/templates`

Damit bleibt die Frontend-Integration stabil, auch wenn Prefix/Version in der Config angepasst werden.
