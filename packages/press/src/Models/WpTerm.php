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
        $this->termTaxonomy->taxonomy = $value;
    }

    public function setDescriptionAttribute($value)
    {
        $this->termTaxonomy->description = $value;
    }

    public function setParentAttribute($value)
    {
        $this->termTaxonomy->parent = $value;
    }

    public function setCountAttribute($value)
    {
        $this->termTaxonomy->count = $value;
    }
}
