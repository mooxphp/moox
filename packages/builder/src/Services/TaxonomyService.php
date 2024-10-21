<?php

namespace Moox\Builder\Services;

class TaxonomyService
{
    public function getTaxonomies(): array
    {
        return config('builder.taxonomies', []);
    }

    public function getTaxonomyModel(string $taxonomy): ?string
    {
        return $this->getTaxonomies()[$taxonomy]['model'] ?? null;
    }

    public function validateTaxonomy(string $taxonomy): void
    {
        $modelClass = $this->getTaxonomyModel($taxonomy);

        if (! $modelClass || ! class_exists($modelClass)) {
            throw new \InvalidArgumentException("Invalid model class for taxonomy: $taxonomy");
        }
    }
}
