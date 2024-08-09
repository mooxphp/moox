<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;

class WpCategory extends WpTerm
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'terms';
    }

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('category', function (Builder $builder) {
            $builder->whereHas('termTaxonomy', function ($query) {
                $query->where('taxonomy', 'category');
            });
        });

        /*

        static::updating(function ($wpCategory) {
            $relatedFieldsChanged = $wpCategory->isRelatedFieldDirty();

            dd('Rel '.$relatedFieldsChanged);

            if ($relatedFieldsChanged) {
                // If a related field has changed, we can update the related table
                $wpCategory->updateRelatedFields();
            }
        });

        */

        static::created(function ($wpCategory) {
            $wpCategory->termTaxonomy()->create([
                'taxonomy' => 'category',
                'description' => $wpCategory->description ?? '',
                'parent' => $wpCategory->getOriginal('parent') ?? 0,
                'count' => $wpCategory->getOriginal('count') ?? 0,
            ]);
        });

        static::updated(function ($wpCategory) {
            $wpCategory->termTaxonomy()->update([
                'description' => $wpCategory->description ?? '',
                'parent' => $wpCategory->getOriginal('parent') ?? 0,
                'count' => $wpCategory->getOriginal('count') ?? 0,
            ]);
        });
    }

    public function isRelatedFieldDirty()
    {
        return $this->description !== $this->getOriginal('description') ||
               $this->parent !== $this->getOriginal('parent') ||
               $this->count !== $this->getOriginal('count');
    }

    public function updateRelatedFields()
    {
        $this->termTaxonomy()->update([
            'description' => $this->description,
            'parent' => $this->parent,
            'count' => $this->count,
        ]);
    }
}
