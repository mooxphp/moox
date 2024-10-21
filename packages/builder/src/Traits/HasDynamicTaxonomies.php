<?php

namespace Moox\Builder\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;

trait HasDynamicTaxonomies
{
    public function taxonomy(string $taxonomy): MorphToMany
    {
        $taxonomies = config('builder.taxonomies', []);

        if (! isset($taxonomies[$taxonomy])) {
            Log::error("Taxonomy not found: $taxonomy");

            return $this->emptyMorphToMany();
        }

        return $this->morphToMany(
            $taxonomies[$taxonomy]['model'],
            $taxonomies[$taxonomy]['relationship'] ?? 'taggable',
            $taxonomies[$taxonomy]['table'] ?? 'taggables',
            $taxonomies[$taxonomy]['foreignKey'] ?? 'taggable_id',
            $taxonomies[$taxonomy]['relatedKey'] ?? 'tag_id'
        )->withTimestamps();
    }

    protected function emptyMorphToMany(): MorphToMany
    {
        return $this->morphToMany(Model::class, 'taggable', 'taggables')->whereRaw('1 = 0');
    }

    public function __call($method, $parameters)
    {
        $taxonomies = config('builder.taxonomies', []);
        if (array_key_exists($method, $taxonomies)) {
            return $this->taxonomy($method);
        }

        return parent::__call($method, $parameters);
    }

    public function syncTaxonomy(string $taxonomy, array $ids): void
    {
        $taxonomies = config('builder.taxonomies', []);
        if (array_key_exists($taxonomy, $taxonomies)) {
            $this->$taxonomy()->sync($ids);
        }
    }
}
