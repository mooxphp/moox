<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Publish\SinglePublishInModel;
use Moox\Core\Traits\Taxonomy\TaxonomyInModel;
use Moox\Core\Traits\UserRelation\UserInModel;

class FullItem extends Model
{
    use HasFactory, SinglePublishInModel, SoftDeletes, TaxonomyInModel, UserInModel;

    protected $table = 'full_items';

    protected function getResourceName(): string
    {
        return 'full-item';
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image_url',
        'gallery_image_urls',
        'type',
        'author_id',
        'publish_at',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'gallery_image_urls' => 'array',
    ];

    public static function getTypeOptions(): array
    {
        return config('builder.types');
    }
}
