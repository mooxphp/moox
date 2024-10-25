<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\AuthorInModel;
use Moox\Core\Traits\StatusInModel;
use Moox\Core\Traits\TaxonomyInModel;

class Item extends Model
{
    use AuthorInModel, HasFactory, SoftDeletes, StatusInModel, TaxonomyInModel;

    protected $table = 'items';

    protected function getResourceName(): string
    {
        return 'item';
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
}
