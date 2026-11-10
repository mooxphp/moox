# Moox Media Package

A comprehensive media management package for FilamentPHP with translation support.

## Installation

Install the package using the Moox installer:

```bash
php artisan moox:install
```

This will:
- Publish migrations and configuration files
- Publish Spatie Media Library configuration
- Integrate the custom Media model and PathGenerator

## Features

- **Media Management**: Upload, organize, and manage media files with Spatie Media Library integration
- **Translations**: Full translation support for media metadata
- **MediaPicker Component**: Filament form component for selecting and attaching media to models
- **Media Collections**: Organize media into collections with translation support
- **Delivery conversions**: AVIF / WebP / JPG at 480 / 960 / 1440 for responsive `<picture>` output
- **HTTP API**: List, show-by-id, and upload — each item includes a `picture` payload

## Usage

### Setup Model for Media

To use media in your model, you need to:

1. Use the `HasMediaUsable` trait
2. Implement `HasMedia` interface
3. Use `InteractsWithMedia` trait from Spatie Media Library
4. Add a JSON field for storing media metadata (e.g., `image`)
5. Optionally add a relation method to access media through usables

Example:

```php
use Illuminate\Database\Eloquent\Model;
use Moox\Media\Traits\HasMediaUsable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Draft extends Model implements HasMedia
{
    use HasMediaUsable, InteractsWithMedia;

    protected $fillable = [
        'image', // JSON field for media metadata
        // ... other fields
    ];

    protected $casts = [
        'image' => 'json',
        // ... other casts
    ];

    // Optional: Access media through usables relation
    public function mediaThroughUsables()
    {
        return $this->belongsToMany(
            Media::class,
            'media_usables',
            'media_usable_id',
            'media_id'
        )->where('media_usables.media_usable_type', '=', static::class);
    }

    // Optional: Register media conversions
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300);
    }
}
```

### Use MediaPicker in Filament Forms

```php
use Moox\Media\Forms\Components\MediaPicker;

MediaPicker::make('image')
    ->multiple(false)
    ->acceptedFileTypes(['image/jpeg', 'image/png'])
```

### Access Media Metadata

When media is attached via MediaPicker, the JSON field contains:

```json
{
    "file_name": "example.jpg",
    "title": "Example Image",
    "alt": "Example Image",
    "description": "Image description",
    "internal_note": "Internal notes"
}
```

### Editing Media Metadata

When editing media in the admin panel or MediaPicker sidebar, fields like `name`, `title`, `alt`, `description`, and `internal_note` are automatically saved when you change a field and leave it (blur). No save button is required. For a locale without a translation yet, an amber notice is shown, `name` is prefilled from the default locale (or the original file name), and a translation row is created only after a real edit — not merely from opening/closing the UI. Changing the language switcher reloads picker metadata for the selected item.

## Delivery profiles (web / mail / customer_api)

Media owns output formats. Templates must not invent their own AVIF/WebP pipelines.

| Profile | Formats | Responsive | Notes |
|---------|---------|------------|--------|
| `web` | AVIF, WebP, original/WP-size fallback | yes (no upscale) | SVG stays SVG (plain `<img>`) |
| `mail` | JPEG/PNG | no | SVG → `mail-png`; fixed width (~600px) |
| `customer_api` | JPEG/PNG (+ optional watermark) | no | **Opt-in** via app `config/media.php`; for external consumers |

`web` and `mail` are always available. `customer_api` stays off until an app enables it. Watermarking is a separate opt-in and only runs when `watermark_path` points at a real image file.

### Widths

WordPress-compatible **admin** sizes (always available when width allows): `thumbnail` (150), `medium` (300), `medium_large` (768), `large` (1024).

**Web srcset** uses widths ≥ `min_responsive_width` (default **480**) plus `extra_widths` (`480` / `960` / `1440`). Typical ladder: **480, 768, 960, 1024, 1440**. Conversions above the original width are **not** created. Tiny sources (&lt; 480px) get no artificial web srcset (admin/mail sizes may still exist).

### Quality defaults

| Format | Quality | Rationale |
|--------|---------|-----------|
| jpg | 82 | Web JPEG sweet spot (~80–85) |
| webp | 80 | ≈ JPEG 80 visually |
| avif | 55 | AVIF scale runs ~15–20 below JPEG |
| png | 90 | UI / logos with transparency |

Queued conversion names look like `web-960-webp`, plus `mail` / `mail-png` / `customer_api`. Config: `config/media.php` → `delivery`. Optimizer / queue / driver: `config/media-library.php`.

When `IMAGE_DRIVER` / `media.image_driver` is `auto` (default), the package prefers **vips → imagick → gd**.

```bash
php artisan queue:work
php artisan media:image-driver                 # resolved driver + optimizer binaries
php artisan media:regenerate-delivery          # missing delivery conversions
php artisan media:regenerate-delivery --force  # rebuild after delivery/watermark config changes
php artisan media:regenerate-delivery --ids=4
```

Normal path: **upload in Filament/API** → queue builds delivery variants. Use `--force` regenerate when enabling or changing watermark/`customer_api` (or other delivery settings) for images that were already uploaded.

Enable optional features in the consuming app via `config/media.php` (publish or merge overrides), for example:

```php
return array_replace_recursive(require base_path('vendor/moox/media/config/media.php'), [
    'delivery' => [
        'profiles' => [
            'customer_api' => [
                'enabled' => true,
                'watermark' => true,
                'watermark_path' => storage_path('app/media-watermark.png'),
            ],
        ],
    ],
]);
```

Admin edit/view lists every variant with an open link. API `MediaItemResource` exposes `picture`, `delivery.web|mail`, and `delivery.customer_api` only when enabled.

A queue worker must be running while `media-library.queue_conversions_by_default` is true (admin sizes are `nonQueued`; delivery variants are queued).

Until delivery conversions finish, `<x-media::picture>` / API payloads may fall back to the original file (except `customer_api`, which never falls back to the original).

### Security notes

- **SVG:** all ingest paths (API, ListMedia upload, MediaPicker, MediaResource replace/edit) run through `MediaPostProcessor` → `SvgSanitizer` (`enshrined/svg-sanitize`, remote references removed). Prefer serving logos as SVG in `<img src>` (not inlined) so script context is limited.
- **customer_api vs original:** When enabled, admin/picker payloads still include top-level `url` (original) for editors. External consumers should use only `delivery.customer_api.url`. If that field is `null`, do not fall back to `url`. SVGs get no `customer_api` conversion (when `skip_static` is enabled, records tagged `static_path` are skipped too).

### Frontend handoff

**Media provides:** delivery profiles, no-upscale rules, SVG sanitize + mail PNG, optional customer API URLs (+ optional watermark), `<x-media::picture profile="web|mail|customer_api">`, variant UI. Templates resolve media via upload/CMS/MediaPicker (`Media::find` / attached relations).

## Using media on pages (all options)

Images must exist in the media library before they can be rendered with delivery formats.

**Options overview**

| Option | Name | Best for |
|--------|------|----------|
| **A** | [`medias` array from the route](#option-a--medias-array-from-the-route-recommended-for-multi-slot-pages) | Several slots on one page; IDs stay in the route |
| **B** | [Load media inline in the Blade view](#option-b--load-media-inline-in-the-blade-view) | Quick single image; `Route::view` without data |
| **C** | [Single model variable from the route](#option-c--single-model-variable-from-the-route) | One hero/teaser passed from the route |
| **D** | [`MediaPictureBuilder` in PHP](#option-d--mediapicturebuilder-in-php) | Non-Blade HTML or JSON structures |
| **E** | [HTTP API `picture` payload](#option-e--http-api-picture-payload-authenticated) | Logged-in editor/picker tooling |
| **F** | [Attach media via MediaPicker](#option-f--attach-media-to-eloquent-models-mediapicker) | Filament forms attaching media to models |

Shared prerequisites:

1. Upload the file in the Filament media admin
2. Wait for the queue (or run `media:regenerate-delivery`) so delivery conversions exist
3. Resolve a `Moox\Media\Models\Media` instance (or use the HTTP API while authenticated)
4. Render via one of the options below

`Media::find(...)` returns `null` if the record is missing — always guard before rendering.

For **public** marketing pages prefer options **A–D**. Use option **E** only with an authenticated session (or a future public auth setup).

### Option A — `medias` array from the route (recommended for multi-slot pages)

Pass an associative array of named slots from the route:

```php
use Illuminate\Contracts\View\View;
use Moox\Media\Models\Media;

$home = static function (string $view): View {
    return view($view, [
        'medias' => [
            'hero' => Media::find(4),
            // 'teaser' => Media::find(12),
        ],
    ]);
};

Route::get('/', fn (): View => $home('heco::de.index'))->name('home');
```

```blade
@if ($medias['hero'] ?? null)
    <x-media::picture
        :media="$medias['hero']"
        sizes="(max-width: 768px) 100vw, 960px"
        class="aspect-[16/7] w-full object-cover object-center"
    />
@endif
```

### Option B — Load media inline in the Blade view

No route changes; works with `Route::view` and hard-coded IDs in the template:

```blade
<section class="pb-0" aria-label="Hero-Bild">
  <div class="overflow-hidden">
    @php($hero = \Moox\Media\Models\Media::find(4))
    @if ($hero)
      <x-media::picture
        :media="$hero"
        sizes="(max-width: 768px) 100vw, 1680px"
        alt="heco"
        class="aspect-[16/7] w-full object-cover object-center"
      />
    @endif
  </div>
</section>
```

### Option C — Single model variable from the route

Same as A, but one variable instead of a `medias` map:

```php
return view('heco::de.index', [
    'heroMedia' => \Moox\Media\Models\Media::find(4),
]);
```

```blade
@if ($heroMedia)
    <x-media::picture
        :media="$heroMedia"
        sizes="(max-width: 768px) 100vw, 960px"
        class="aspect-[16/7] w-full object-cover object-center"
    />
@endif
```

### Option D — `MediaPictureBuilder` in PHP

Build HTML or a structured array without the Blade component (controllers, mail, custom responses):

```php
use Moox\Media\Models\Media;
use Moox\Media\Support\MediaPictureBuilder;

$media = Media::findOrFail(4);
$builder = app(MediaPictureBuilder::class);

$html = $builder->toHtml(
    $media,
    sizes: '(max-width: 768px) 100vw, 960px',
    alt: 'heco',
    attributes: ['class' => 'aspect-[16/7] w-full object-cover object-center'],
);

$array = $builder->toArray($media, sizes: '(max-width: 768px) 100vw, 960px');
// $array = ['alt' => …, 'sizes' => …, 'sources' => [[type, srcset], …], 'img' => [src, srcset, loading, decoding]]
```

### Option E — HTTP API `picture` payload (authenticated)

Use when an editor/tool selects media over HTTP. Not for anonymous public page `fetch` (API middleware is `web` + `auth` by default).

```http
GET /api/media/4
```

Response `data.picture` matches the Blade/builder structure (`sources` + `img`). Full endpoint docs: [API.md](API.md).

Summary:

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/api/media` | Paginated list (`search`, `type`, `collection`, `lang`, …) |
| `GET` | `/api/media/{id}` | Single item including `picture` |
| `POST` | `/api/media` | Upload into the library |

Configurable via `config/media.php` → `api`.

### Option F — Attach media to Eloquent models (MediaPicker)

For CMS records that store a media snapshot on the model (JSON field + `media_usables`), use the MediaPicker flow documented under [Usage](#usage) above.

### `<x-media::picture>` attributes

Used by options A–C:

- `media` — required `Moox\Media\Models\Media` instance
- `sizes` — HTML `sizes` hint for the browser (does not change generated widths)
- `alt` — overrides translated alt from the media record
- Any extra HTML attributes (for example `class`) are applied to the inner `<img>`

Output shape (AVIF → WebP → JPG fallback), same for options A–E:

```html
<picture>
  <source type="image/avif" srcset="…/delivery-480-avif.avif 480w, … 960w, … 1440w" sizes="…">
  <source type="image/webp" srcset="…/delivery-480-webp.webp 480w, …" sizes="…">
  <img src="…/delivery-960-jpg.jpg" srcset="… 480w, … 960w, … 1440w" sizes="…" loading="lazy" decoding="async" alt="…">
</picture>
```

## Requirements

- Laravel 12+
- Filament 4+
- Spatie Media Library
- Astrotomic Translatable
