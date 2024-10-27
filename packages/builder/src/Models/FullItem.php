<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\AuthorInModel;
use Moox\Core\Traits\SinglePublishInModel;
use Moox\Core\Traits\TaxonomyInModel;

class FullItem extends Model
{
    use AuthorInModel, HasFactory, SinglePublishInModel, SoftDeletes, TaxonomyInModel;

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
