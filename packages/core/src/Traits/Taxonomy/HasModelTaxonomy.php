<?php

namespace Moox\Core\Traits\Taxonomy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;

trait HasModelTaxonomy
{
    use HasTaxonomyService;

    /**
     * Get the taxonomy relation.
     */
    public function taxonomy(string $taxonomy): MorphToMany
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();

        if (! isset($taxonomies[$taxonomy])) {
            Log::error('Taxonomy not found: '.$taxonomy);

            return $this->emptyMorphToMany();
        }

        return $this->morphToMany(
            $this->getTaxonomyService()->getTaxonomyModel($taxonomy),
            $this->getTaxonomyService()->getTaxonomyRelationship($taxonomy),
            $this->getTaxonomyService()->getTaxonomyTable($taxonomy),
            $this->getTaxonomyService()->getTaxonomyForeignKey($taxonomy),
            $this->getTaxonomyService()->getTaxonomyRelatedKey($taxonomy)
        )->withTimestamps();
    }

    protected function emptyMorphToMany(): MorphToMany
    {
        return $this->morphToMany(Model::class, 'taggable', 'taggables')->whereRaw('1 = 0');
    }

    /**
     * Handle dynamic method calls to the taxonomy relation.
     */
    public function __call($method, $parameters)
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();
        if (array_key_exists($method, $taxonomies)) {
            return $this->taxonomy($method);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Sync the taxonomy.
     */
    public function syncTaxonomy(string $taxonomy, array $ids): void
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();
        if (array_key_exists($taxonomy, $taxonomies)) {
            $this->$taxonomy()->sync($ids);
        }
    }
}
