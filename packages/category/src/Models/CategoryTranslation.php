<?php

namespace Moox\Category\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class CategoryTranslation extends BaseDraftTranslationModel
{
    use SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        'title',
        'locale',
        'slug',
        'permalink',
        'content',
        'to_publish_at',
        'published_at',
        'to_unpublish_at',
        'unpublished_at',
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

        // Created by fields
        'created_by_id',
        'created_by_type',

        // Updated by fields
        'updated_by_id',
        'updated_by_type',
    ];

    protected $casts = [
        'data' => 'json',
        'to_publish_at' => 'datetime',
        'published_at' => 'datetime',
        'to_unpublish_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
