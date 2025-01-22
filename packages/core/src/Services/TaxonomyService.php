<?php

namespace Moox\Core\Services;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use RuntimeException;

class TaxonomyService
{
    private ?string $currentResource = null;

    public function setCurrentResource(string $resource): void
    {
        $this->currentResource = $resource;
    }

    public function getCurrentResource(): ?string
    {
        return $this->currentResource;
    }

    private function ensureResourceIsSet(): void
    {
        if ($this->currentResource === null) {
            throw new RuntimeException('Current resource is not set. Call setCurrentResource() first.');
        }
    }

    public function getTaxonomies(): array
    {
        $this->ensureResourceIsSet();

        return Config::get(sprintf('previews.%s.taxonomies', $this->currentResource), [])
            ?: Config::get(sprintf('builder.resources.%s.taxonomies', $this->currentResource), []);
    }

    public function getTaxonomyModel(string $taxonomy): ?string
    {
        return $this->getTaxonomies()[$taxonomy]['model'] ?? null;
    }

    public function validateTaxonomy(string $taxonomy): void
    {
        $modelClass = $this->getTaxonomyModel($taxonomy);

        if (! $modelClass || ! class_exists($modelClass)) {
            throw new InvalidArgumentException(sprintf('Invalid model class for taxonomy: %s in resource: %s', $taxonomy, $this->currentResource));
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

    public function hasTaxonomies(): bool
    {
        $this->ensureResourceIsSet();

        return $this->getTaxonomies() !== [];
    }
}
