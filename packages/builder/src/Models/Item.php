<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

    public function taxonomy(string $taxonomy): MorphToMany
    {
        $taxonomies = config('builder.taxonomies', []);

        if (! isset($taxonomies[$taxonomy])) {
            Log::error("Taxonomy not found: $taxonomy");

            return $this->morphToMany(Model::class, 'taggable', 'taggables')->where('id', 0);
        }

        return $this->morphToMany(
            $taxonomies[$taxonomy]['model'],
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id'
        )->withTimestamps();
    }

    protected function nullRelation()
    {
        return new class
        {
            public function all()
            {
                return collect();
            }

            public function get()
            {
                return collect();
            }

            public function count()
            {
                return 0;
            }

            public function getResults()
            {
                return collect();
            }
        };
    }

    protected static function newFactory(): mixed
    {
        return ItemFactory::new();
    }

    public function __call($method, $parameters)
    {
        $taxonomies = config('builder.taxonomies', []);
        if (array_key_exists($method, $taxonomies)) {
            return $this->morphToMany(
                $taxonomies[$method]['model'],
                'taggable',
                'taggables',
                'taggable_id',
                'tag_id'
            )->withTimestamps();
        }

        // If it's not a taxonomy, try to get the relation
        $relation = parent::__call($method, $parameters);

        // If the relation is null, return an empty collection to avoid the getResults() error
        if ($relation === null) {
            return new Collection;
        }

        return $relation;
    }

    // This method will handle getting taxonomy attributes dynamically
    public function getTaxonomyAttribute($key)
    {
        $taxonomies = config('builder.taxonomies', []);
        if (array_key_exists($key, $taxonomies)) {
            $relation = $this->$key();

            return $relation instanceof MorphToMany ? $relation->get() : new Collection;
        }

        return parent::getAttribute($key);
    }
}
