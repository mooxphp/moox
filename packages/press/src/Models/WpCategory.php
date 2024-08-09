<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WpCategory extends WpTerm
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'term_group'];

    protected $appends = [
        'taxonomy',
        'description',
        'parent',
        'count',
    ];

    protected $searchableFields = ['*'];

    protected $wpPrefix;

    protected $table;

    protected $primaryKey = 'term_id';

    public $timestamps = false;

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
            $wpCategory->termTaxonomy()->create([
                'taxonomy' => 'category',
                'description' => $wpCategory->attributes['description'] ?? '',
                'parent' => $wpCategory->attributes['parent'] ?? 0,
            ]);
        });

        static::updated(function ($wpCategory) {
            $wpCategory->termTaxonomy()->update([
                'description' => $wpCategory->attributes['description'] ?? '',
                'parent' => $wpCategory->attributes['parent'] ?? 0,
            ]);
        });
    }
}
