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
