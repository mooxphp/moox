# Moox Media Library

A powerful media library package for Laravel and Filament, built on top of the Spatie Media Library.

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or higher
- Filament 3.x
- Spatie Media Library

## Features

- Beautiful media library interface
- Advanced search and filtering
- Collection management
- Media usage tracking
- Write protection for important media
- Smart media replacement (automatically updates all usages)
- Media picker for easy integration

## Installation

```bash
# Install the package
composer require moox/media

# Run the installation command
php artisan media:install

# Install Dependencies
php artisan localization:install
php artisan data:install
```

The installation command will:
- Publish configuration files
- Run necessary migrations
- Register required plugins
- Configure Spatie Media Library integration

### Manual Publishing

If you need to manually publish any assets, you can use these commands:

```bash
# Publish Moox Media configuration
php artisan vendor:publish --tag=media-config

# Publish Spatie Media Library configuration
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"

# Publish Moox Media migrations
php artisan vendor:publish --tag=media-migrations

# Publish Spatie Media Library migrations
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

## Usage

### Media Library

The media library provides a comprehensive interface for managing your media files:

- Upload files with drag-and-drop support
- Configure upload behavior through media.php config (multiple, max files, file types, etc.)
- Organize files into collections
- Search and filter media files
- View and manage file metadata
- Track media usage across your application
- Replace images while maintaining all usages
- Prevent accidental deletion with write protection
- Handle duplicate files with smart detection

### Collections

Collections help you organize your media files:

- Create and manage collections
- Assign files to specific collections
- Filter media by collection
- Set collection-specific permissions
- Use collections in the media picker

### Media Picker Integration

To use the media picker in your model, you need to:

1. Add the required traits to your model:
```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class YourModel extends Model implements HasMedia
{
    use InteractsWithMedia;
}
```

2. Add the mediaThroughUsables relationship:
```php
use Moox\Media\Models\Media;

public function mediaThroughUsables()
{
    return $this->belongsToMany(
        Media::class,
        'media_usables',
        'media_usable_id',
        'media_id'
    )->where('media_usables.media_usable_type', '=', static::class);
}
```

3. Use the CustomImageColumn in your Filament resource:
```php
use Moox\Media\Tables\Columns\CustomImageColumn;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            CustomImageColumn::make('media')
                ->circular(),
            // ... other columns
        ]);
}
```

4. Use the MediaPicker component in your form:
```php
use Moox\Media\Forms\Components\MediaPicker;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            MediaPicker::make('media')
                ->collection('images')
                ->multiple()
                ->image(),
            // ... other fields
        ]);
}
```

## Support

For questions or issues:
- Create an issue on GitHub
- Contact us through our support system 