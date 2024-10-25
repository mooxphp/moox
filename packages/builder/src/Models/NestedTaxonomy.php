<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Builder\Database\Factories\ItemFactory;
use Moox\Core\Traits\TaxonomyInModel;

class NestedTaxonomy extends Model
{
    use HasFactory, SoftDeletes, TaxonomyInModel;

    protected $table = 'nested_taxonomies';

    protected function getResourceName(): string
    {
        return 'nested_taxonomy';
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'color',
        'weight',
        'count',
        'featured_image_url',
        'parent_id',
    ];

    protected $casts = [
        'weight' => 'integer',
        'count' => 'integer',
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
        if ($this->trashed()) {
            return 'deleted';
        }

        return $this->getAttribute('publish_at')
            ? ($this->getAttribute('publish_at')->isFuture() ?
            'scheduled' : 'published')
            : 'draft';
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
