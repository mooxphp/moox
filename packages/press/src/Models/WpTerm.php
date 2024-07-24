<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpTerm extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'term_group'];

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

    public function termTaxonomies()
    {
        return $this->hasMany(WpTermTaxonomy::class, 'term_id', 'term_id');
    }
}
