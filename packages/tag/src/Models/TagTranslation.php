<?php

namespace Moox\Tag\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class TagTranslation extends BaseDraftTranslationModel
{
    use SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        'locale',
        'tag_id',
        'title',
        'slug',
        'permalink',
        'content',
        'author_id',
        'author_type',
        'to_publish_at',
        'published_at',
        'to_unpublish_at',
        'unpublished_at',
        'published_by_id',
        'unpublished_by_id',
        'unpublished_by_type',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'string',
        'slug' => 'string',
        'content' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
    ];
}
