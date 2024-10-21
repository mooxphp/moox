<?php

namespace Moox\Core\Services;

use Illuminate\Support\Facades\Config;

class TaxonomyService
{
    public function getTaxonomies(): array
    {
        return Config::get('builder.taxonomies', []);
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

    public function getTaxonomyRelationship(string $taxonomy): string
    {
        return $this->getTaxonomies()[$taxonomy]['relationship'] ?? 'taggable';
    }

    public function getTaxonomyTable(string $taxonomy): string
    {
        return $this->getTaxonomies()[$taxonomy]['table'] ?? 'taggables';
    }

    public function getTaxonomyForeignKey(string $taxonomy): string
    {
        return $this->getTaxonomies()[$taxonomy]['foreignKey'] ?? 'taggable_id';
    }

    public function getTaxonomyRelatedKey(string $taxonomy): string
    {
        return $this->getTaxonomies()[$taxonomy]['relatedKey'] ?? 'tag_id';
    }
}
