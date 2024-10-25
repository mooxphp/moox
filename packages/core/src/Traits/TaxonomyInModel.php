<?php

namespace Moox\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Log;
use Moox\Core\Services\TaxonomyService;

trait TaxonomyInModel
{
    protected ?TaxonomyService $taxonomyService = null;

    protected function getTaxonomyService(): TaxonomyService
    {
        if ($this->taxonomyService === null) {
            $this->taxonomyService = app(TaxonomyService::class);
            $this->taxonomyService->setCurrentResource($this->getResourceName());
        }

        return $this->taxonomyService;
    }

    protected function getResourceName(): string
    {
        return static::getModel()::getResourceName();
    }

    public function taxonomy(string $taxonomy): MorphToMany
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();

        if (! isset($taxonomies[$taxonomy])) {
            Log::error("Taxonomy not found: $taxonomy");

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

    public function __call($method, $parameters)
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();
        if (array_key_exists($method, $taxonomies)) {
            return $this->taxonomy($method);
        }

        return parent::__call($method, $parameters);
    }

    public function syncTaxonomy(string $taxonomy, array $ids): void
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();
        if (array_key_exists($taxonomy, $taxonomies)) {
            $this->$taxonomy()->sync($ids);
        }
    }
}
