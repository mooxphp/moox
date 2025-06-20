<?php

namespace Moox\Category\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class CategoryTranslation extends BaseDraftTranslationModel
{
    public $timestamps = true;
    protected $fillable = [
        'title',
        'locale',
        'status',
        'slug',
        'content',
        'to_publish_at',
        'published_at', 
        'to_unpublish_at',
        'unpublished_at',
        'author_id',
        'data'
    ];

    protected $casts = [
        'data' => 'json',
    ];

}
