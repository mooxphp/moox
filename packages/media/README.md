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

When editing media in the admin panel, fields like `title`, `alt`, `description`, and `internal_note` are automatically saved as soon as you leave the field (blur event). No save button is required - changes are persisted immediately and synchronized to all models using that media.

## Requirements

- Laravel 12+
- Filament 4+
- Spatie Media Library
- Astrotomic Translatable
