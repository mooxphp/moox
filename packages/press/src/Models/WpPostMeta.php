<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $meta_value
 * @property string $meta_key
 */
class WpPostMeta extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'meta_key', 'meta_value'];

    protected $searchableFields = ['*'];

    protected $wpPrefix;

    protected $table;

    protected $primaryKey = 'meta_id';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'postmeta';
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(WpPost::class, 'post_id', 'ID');
    }
}
