<?php

namespace Moox\Draft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DraftTranslation extends Model
{
    use SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        // Translation fields
        'locale',
        'draft_id',
        'title',
        'slug',
        'description',
        'content',
        'author_id',

        // Publishing schedule fields
        'to_publish_at',
        'published_at',
        'to_unpublish_at',
        'unpublished_at',

        // Actor fields
        'published_by_id',
        'published_by_type',
        'unpublished_by_id',
        'unpublished_by_type',

        // Soft delete and restoration fields
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
    ];

    protected $casts = [
        // DateTime casts
        'to_publish_at' => 'datetime',
        'published_at' => 'datetime',
        'to_unpublish_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
    ];
}
