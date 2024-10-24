<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Builder\Database\Factories\ItemFactory;
use Moox\Core\Traits\HasDynamicTaxonomies;

class Item extends Model
{
    use HasDynamicTaxonomies, HasFactory, SoftDeletes;

    protected $table = 'items';

    protected function getResourceName(): string
    {
        return 'builder';
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

    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'published' => 'Published',
            'deleted' => 'Deleted',
        ];
    }

    public function getStatusAttribute(): string
    {
        if ($this->deleted_at) {
            return 'deleted';
        }

        if (! $this->publish_at) {
            return 'draft';
        }

        return $this->publish_at->isFuture() ? 'scheduled' : 'published';
    }

    public function author(): ?BelongsTo
    {
        $authorModel = config('builder.author_model');
        if ($authorModel && class_exists($authorModel)) {
            return $this->belongsTo($authorModel, 'author_id');
        }

        return null;
    }

    protected static function newFactory(): mixed
    {
        return ItemFactory::new();
    }
}
