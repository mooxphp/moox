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

## Delivery profiles

Media owns output formats. Templates must not invent their own AVIF/WebP pipelines. Every key under `media.delivery.profiles` is a profile — apps can add their own (`og`, `feed`, …). The package ships **no brand assets** (watermarks live in the app).

### Default profiles

| Profile | Mode | Formats | Notes |
|---------|------|---------|--------|
| `web` | `responsive` | AVIF, WebP, original fallback | SVG = original `<img>`; no upscale |
| `mail` | `single` | JPEG/PNG | SVG → `mail-png`; ~600px |
| `customer_api` | `single` | JPEG/PNG | **Opt-in**; optional watermark; `fallback_to_original=false`; `svg=skip` |

### Profile config keys

```php
'my_profile' => [
    'enabled' => true,
    'mode' => 'responsive', // or 'single'
    'formats' => ['avif', 'webp', 'original'],
    'upscale' => false,
    'min_responsive_width' => 480, // responsive
    'widths' => [480, 960, 1440],  // optional; else global extra_widths
    'default_width' => 1200,       // single
    'fallback_to_original' => true,
    'svg' => 'original',           // original | png | skip
    'skip_static' => false,
    'watermark' => [
        'enabled' => false,
        'path' => null, // absolute or relative to base_path(); app file only
        'opacity' => 40,
        'width_percent' => 15,
        'padding_percent' => 3,
    ],
],
```

### Widths & edge cases

WordPress-compatible **admin** sizes (when width allows): `thumbnail` (150), `medium` (300), `medium_large` (768), `large` (1024).

| Case | Behaviour |
|------|-----------|
| Original ≥ `min_responsive_width` | Width ladder (WP sizes ≥ min + profile `widths` / global `extra_widths`), no upscale |
| Original &lt; min (e.g. nav 240px) | **Same-size** AVIF/WebP/JPG at original width — no multi-width srcset |
| `image/gif` (default `skip_mime_types`) | No delivery profiles; keep animated original (+ small admin thumbs) |
| Unknown dimensions | No responsive ladder guessed; admin thumbs ≤ 300px only |
| SVG | Per profile `svg`: `original` / `png` / `skip` |

### Quality defaults

| Format | Quality | Rationale |
|--------|---------|-----------|
| jpg | 82 | Web JPEG sweet spot (~80–85) |
| webp | 80 | ≈ JPEG 80 visually |
| avif | 55 | AVIF scale runs ~15–20 below JPEG |
| png | 90 | UI / logos with transparency |

Conversion names: responsive/same-size `{profile}-{width}-{format}`; single = profile key; SVG PNG = `{profile}-png`. Config: `config/media.php` → `delivery`. Optimizer / queue / driver: `config/media-library.php`.

When `IMAGE_DRIVER` / `media.image_driver` is `auto` (default), the package prefers **vips → imagick → gd**.

### Regenerate (prefer targeted)

Normal path: **upload** → queue builds variants. Prefer selecting media in the Mediathek bulk action, or CLI with `--ids` / `--profile`. Full-library scans are intentional ops only.

```bash
php artisan queue:work
php artisan media:image-driver
php artisan media:regenerate-delivery --ids=4 --ids=12
php artisan media:regenerate-delivery --profile=customer_api --force
php artisan media:regenerate-delivery --profile=web,mail --ids=4
# library-wide missing only (warns when no --ids):
php artisan media:regenerate-delivery
php artisan media:regenerate-delivery --force   # after watermark/config change
```

Use `--force` after enabling profiles or changing watermark settings.

Publish and edit the app config as usual (`php artisan vendor:publish --tag=media-config`), then enable what you need — e.g. customer API + watermark from **app** storage:

```php
'customer_api' => [
    'enabled' => true,
    // …
    'watermark' => [
        'enabled' => true,
        'path' => resource_path('watermarks/signet.png'),
        'opacity' => 40,
        'width_percent' => 15,
        'padding_percent' => 3,
        'position' => 'bottom_right',
        'fit' => 'contain',
    ],
],
```

Admin lists every variant. API `delivery` includes every **enabled** profile (`responsive` → picture payload, `single` → `{ url }`).

A queue worker must be running while `media-library.queue_conversions_by_default` is true (admin sizes are `nonQueued`; delivery variants are queued).

Until conversions finish, payloads may fall back to the original when `fallback_to_original` is true (default for `web`/`mail`; false for `customer_api`).

### Security notes

- **SVG:** all ingest paths run through `MediaPostProcessor` → `SvgSanitizer` (`enshrined/svg-sanitize`). Prefer logos as `<img src="…svg">` (not inlined).
- **Protected profiles:** when `fallback_to_original` is false, never use top-level `url` for external consumers — only `delivery.{profile}.url`. With `skip_static`, records tagged `static_path` are skipped.

### Rendering (Spatie Media Library + Moox profiles)

Uses Spatie’s rendering API and extends it with delivery profiles:

```blade
{{-- Spatie-style conversion / original --}}
{{ $media->img() }}
{{ $media->img('thumb')->lazy()->attributes(['class' => 'rounded']) }}
<x-media::img :media="$media" conversion="medium" class="rounded" lazy />

{{-- Moox delivery profile (<picture> / same-size / mail / …) --}}
{{ $media->img()->profile('web')->sizes('100vw')->alt('Hero') }}
<x-media::picture :media="$media" profile="web" sizes="100vw" alt="Hero" />
```

Watermarks are applied via `Moox\Media\Support\MediaWatermark` (Spatie Image `watermark()`), only when a profile enables them and points at an **app** file path — never a package brand asset.

### Frontend handoff

**Media provides:** config-driven delivery profiles, Spatie `img()` rendering + profile HTML, no-upscale rules, SVG sanitize + optional mail PNG, optional watermarks (app path), `<x-media::img>` / `<x-media::picture>`, variant UI. Resolve media via upload/CMS/MediaPicker (`Media::find` / relations).

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
