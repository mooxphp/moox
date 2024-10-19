<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Builder\Database\Factories\ItemFactory;
use Moox\Core\Traits\HasSlug;

class Item extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $table = 'items';

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

    public function taxonomies(): array
    {
        $relations = [];
        foreach (config('builder.taxonomies') as $taxonomy => $settings) {
            $relations[$taxonomy] = function () use ($settings) {
                return $this->belongsToMany($settings['model']);
            };
        }

        return $relations;
    }

    // TODO: Working but hardcoded to tags, need to make it dynamic.
    // could be a trait that we load based on the config?
    public function tags(): MorphToMany
    {
        return $this->morphToMany(config('builder.taxonomies.tags.model'), 'taggable');
    }

    protected static function newFactory(): ItemFactory
    {
        return ItemFactory::new();
    }
}
