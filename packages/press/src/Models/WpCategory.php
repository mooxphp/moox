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
    }

    public function termTaxonomy()
    {
        return $this->hasOne(WpTermTaxonomy::class, 'term_id', 'term_id');
    }

    public function getTaxonomyAttribute()
    {
        return $this->termTaxonomy->taxonomy ?? '';
    }

    public function setTaxonomyAttribute($value)
    {
        $this->termTaxonomy()->updateOrCreate([], ['taxonomy' => 'category']);
    }

    public function getDescriptionAttribute()
    {
        return $this->termTaxonomy->description ?? '';
    }

    public function setDescriptionAttribute($value)
    {
        $this->termTaxonomy()->updateOrCreate([], ['parent' => $value]);
    }

    public function getParentAttribute()
    {
        return $this->termTaxonomy->parent ?? '';
    }

    public function setParentAttribute($value)
    {
        $this->termTaxonomy()->updateOrCreate([], ['parent' => $value]);
    }

    public function getCountAttribute()
    {
        return $this->termTaxonomy->count ?? '';
    }

    public function setCountAttribute($value)
    {
        $this->termTaxonomy()->updateOrCreate([], ['count' => $value]);
    }
}
