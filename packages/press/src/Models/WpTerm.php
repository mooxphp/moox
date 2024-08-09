<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'term_group',
        'taxonomy',
        'description',
        'parent',
        'count',
    ];

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

    public function termTaxonomy()
    {
        return $this->hasOne(WpTermTaxonomy::class, 'term_id', 'term_id');
    }

    public function getTaxonomyAttribute()
    {
        return $this->termTaxonomy->taxonomy ?? '';
    }

    public function getDescriptionAttribute()
    {
        return $this->termTaxonomy->description ?? '';
    }

    public function getParentAttribute()
    {
        return $this->termTaxonomy->parent ?? 0;
    }

    public function getCountAttribute()
    {
        return $this->termTaxonomy->count ?? 0;
    }

    public function setTaxonomyAttribute($value)
    {
        if ($this->term_id) {
            $this->termTaxonomy()->updateOrCreate([], ['taxonomy' => $value]);
        }
    }

    public function setDescriptionAttribute($value)
    {
        if ($this->term_id) {
            $this->termTaxonomy()->updateOrCreate([], ['description' => $value]);
        }
    }

    public function setParentAttribute($value)
    {
        if ($this->term_id) {
            $this->termTaxonomy()->updateOrCreate([], ['parent' => $value]);
        }
    }

    public function setCountAttribute($value)
    {
        if ($this->term_id) {
            $this->termTaxonomy()->updateOrCreate([], ['count' => $value]);
        }
    }
}
