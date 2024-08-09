<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Moox\Core\Traits\RequestInModel;

class WpCategory extends WpTerm
{
    use RequestInModel;

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

        static::created(function ($wpCategory) {

            $description = $wpCategory->getRequestData('description') ?? '';
            $parent = $wpCategory->getRequestData('parent') ?? 0;
            $count = $wpCategory->getRequestData('count') ?? 0;

            // Todo: fallback to the original values if the request data is empty
            // Todo: fallback to the config values if both are empty

            $wpCategory->termTaxonomy()->create([
                'taxonomy' => 'category',
                'description' => $description,
                'parent' => $parent,
                'count' => $count,
            ]);
        });

        static::updated(function ($wpCategory) {

            // Todo: implement the same logic as in the created event?

            $wpCategory->termTaxonomy()->update([
                'description' => Request::input('description') ?? '',
                'parent' => Request::input('parent') ?? 0,
                'count' => Request::input('count') ?? 0,
            ]);
        });
    }
}
