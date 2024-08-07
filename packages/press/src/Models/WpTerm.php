<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpTerm extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'term_group'];

    protected $searchableFields = ['*'];

    protected static $wpPrefix;

    protected $table;

    protected $primaryKey = 'term_id';

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        self::$wpPrefix = config('press.wordpress_prefix');
        $this->table = self::$wpPrefix.'terms';
    }

    public function termTaxonomy()
    {
        return $this->hasOne(WpTermTaxonomy::class, 'term_id', 'term_id');
    }

    protected static function boot()
    {
        parent::boot();

    }

    // Accessor for termTaxonomy description
    public function getDescriptionAttribute()
    {
        return $this->attributes['description'] ?? '';
    }

    // Mutator for termTaxonomy description
    public function setDescriptionAttribute($value)
    {
        $this->termTaxonomy()->updateOrCreate([], ['description' => $value]);
    }
}
