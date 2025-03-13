# Idea

-   [ ] Config default to Month/Year and specific folders, where applicable.
-   [ ] Collections are not like categories, we can implement this later.

Use

-   [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)
-   [filament-spatie-media-library](https://filamentphp.com/plugins/filament-spatie-media-library)

See also

-   https://filamentphp.com/docs/3.x/forms/fields/custom
-   https://github.com/tomatophp/filament-media-manager
-   https://youtu.be/iehQZSqlduY?si=rikjuhJPzyrZBJRr&t=233 - image editor
-   https://github.com/livewire-filemanager/filemanager - file manager, not media

## Write protected media

1. Model-Level Protection

```php
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CustomMedia extends Media
{
    protected $casts = [
        'write_protected' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($media) {
            if ($media->write_protected) {
                throw new \Exception("This media item is write-protected.");
            }
        });

        static::deleting(function ($media) {
            if ($media->write_protected) {
                throw new \Exception("Cannot delete write-protected media.");
            }
        });
    }
}
```

This ensures that even if an API call or backend modification bypasses Filament, the database enforces write protection.

2. Filament Protection via Policies

```php
use App\Models\CustomMedia;

class MediaPolicy
{
    public function update(User $user, CustomMedia $media)
    {
        return !$media->write_protected;
    }

    public function delete(User $user, CustomMedia $media)
    {
        return !$media->write_protected;
    }
}
```

3 .Register it in AuthServiceProvider:

```php
protected $policies = [
    CustomMedia::class => MediaPolicy::class,
];
```

4. Hide Actions for Protected Media

```php
public static function table(Table $table): Table
{
    return $table
        ->actions([
            EditAction::make()->visible(fn ($record) => !$record->write_protected),
            DeleteAction::make()->visible(fn ($record) => !$record->write_protected),
        ]);
}
```

5. API-Sourced Media Protection

If media items are synced from an external API, ensure write_protected = true on API-imported media:

```php
$media = CustomMedia::create([
    'name' => $apiData['name'],
    'path' => $apiData['path'],
    'write_protected' => true, // API-defined content
]);
```
