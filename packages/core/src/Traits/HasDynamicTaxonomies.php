<?php

namespace Moox\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;
use Moox\Core\Services\TaxonomyService;

trait HasDynamicTaxonomies
{
    protected TaxonomyService $taxonomyService;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->taxonomyService = app(TaxonomyService::class);
    }

    public function taxonomy(string $taxonomy): MorphToMany
    {
        $taxonomies = $this->taxonomyService->getTaxonomies();

        if (! isset($taxonomies[$taxonomy])) {
            Log::error("Taxonomy not found: $taxonomy");

            return $this->emptyMorphToMany();
        }

        return $this->morphToMany(
            $this->taxonomyService->getTaxonomyModel($taxonomy),
            $this->taxonomyService->getTaxonomyRelationship($taxonomy),
            $this->taxonomyService->getTaxonomyTable($taxonomy),
            $this->taxonomyService->getTaxonomyForeignKey($taxonomy),
            $this->taxonomyService->getTaxonomyRelatedKey($taxonomy)
        )->withTimestamps();
    }

    protected function emptyMorphToMany(): MorphToMany
    {
        return $this->morphToMany(Model::class, 'taggable', 'taggables')->whereRaw('1 = 0');
    }

    public function __call($method, $parameters)
    {
        $taxonomies = $this->taxonomyService->getTaxonomies();
        if (array_key_exists($method, $taxonomies)) {
            return $this->taxonomy($method);
        }

        return parent::__call($method, $parameters);
    }

    public function syncTaxonomy(string $taxonomy, array $ids): void
    {
        $taxonomies = $this->taxonomyService->getTaxonomies();
        if (array_key_exists($taxonomy, $taxonomies)) {
            $this->$taxonomy()->sync($ids);
        }
    }
}
