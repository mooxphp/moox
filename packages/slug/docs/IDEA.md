# Idea

See
https://laravel-news.com/generating-slugs-from-a-title-in-filament

Moox Slug should replace https://github.com/adrolli/filament-title-with-slug

-   Provides a combined input field Title With Slug, forked from Camya
-   Uses Spatie's Sluggable under the hood
-   Can (optionally, PRO) Manage Slugs
    -   Slugs are generated as a route cache?
    -   Slugs can automatically create 301 redirects
    -   Slugs can be used to implement a link shortener
    -   Slugs cannot be deleted if referring to an active model
    -   Fields
        -   full url
        -   route?
        -   from model?
        -   Redirect
        -   Redirect type (permanent / temporary)
        -   target
        -   status_code 200, 301, 302, 404
        -   created_at
        -   created_by
        -   created_using
        -   updated_at
        -   updated_by
        -   updated_using
        -   comment
-   Configuration options
    -   Use UI (true), should disable assets, too
    -   Load assets (true)
    -   Default title (title)
    -   Default slug (slug)
    -   URL Host (APP_URL)
    -   Change on Edit (true)
    -   Change when published (false)
    -   Slug Label (Slug:)
    -   Show Visit-Link (true)
    -   Enable Permalinks (true)
    -   Model Slug Binding (can also be registered by a package)

```php
<?php



namespace Moox\Core\Traits;



use Illuminate\Database\Eloquent\Model;

use Spatie\Sluggable\HasSlug as SpatieHasSlug;

use Spatie\Sluggable\SlugOptions;



trait HasSlug

{

use SpatieHasSlug;



public static function bootHasSlug()

{

static::saving(function (Model $model) {

if (! $model->exists && empty($model->getAttribute('slug'))) {

$model->setAttribute('slug', static::generateUniqueSlugStatic($model->getAttribute('title')));

}

});

}



public function getSlugOptions(): SlugOptions

{

return SlugOptions::create()

->generateSlugsFrom('title')

->saveSlugsTo('slug')

->slugsShouldBeNoLongerThan(255)

->doNotGenerateSlugsOnUpdate();

}



public function generateSlug()

{

$this->slug = $this->generateUniqueSlug($this->title);

}



protected static function generateUniqueSlug($value)

{

$slug = \Str::slug($value);

$originalSlug = $slug;

$count = 1;



while (static::where('slug', $slug)->exists()) {

$slug = $originalSlug.'-'.$count++;

}



return $slug;

}



protected function slugExists($slug)

{

return static::where('slug', $slug)

->where('id', '!=', $this->id ?? null)

->exists();

}



public static function generateUniqueSlugStatic($value): string

{

return static::generateUniqueSlug($value);

}

}
```
