<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $term_taxonomy_id
 * @property int $term_id
 * @property string $taxonomy
 * @property string $description
 * @property int $parent
 * @property int $count
 * @property \Moox\Press\Models\WpTerm $term
 */
class WpTermTaxonomy extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_id',
        'taxonomy',
        'description',
        'parent',
        'count',
    ];

    protected $searchableFields = ['*'];

    protected $wpPrefix;

    protected $table;

    protected $primaryKey = 'term_taxonomy_id';

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix . 'term_taxonomy';
    }

    public function term()
    {
        return $this->belongsTo(WpTerm::class, 'term_id', 'term_id');
    }

    public function posts()
    {
        return $this->belongsToMany(WpPost::class, 'term_relationships', 'term_taxonomy_id', 'object_id');
    }
}
