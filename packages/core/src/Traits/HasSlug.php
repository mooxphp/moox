<?php

namespace Moox\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug()
    {
        static::saving(function (Model $model) {
            if (! $model->exists && empty($model->getAttribute('slug'))) {
                $model->setAttribute('slug', static::generateUniqueSlug($model->getAttribute('title')));
            }
        });
    }

    public function generateSlug()
    {
        $this->slug = static::generateUniqueSlug($this->title);
    }

    protected static function generateUniqueSlug($value)
    {
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    public static function generateUniqueSlugStatic($value): string
    {
        return static::generateUniqueSlug($value);
    }
}
