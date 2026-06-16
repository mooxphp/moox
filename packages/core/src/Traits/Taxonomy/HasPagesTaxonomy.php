<?php

/*
 |  Attention!
 |
 |  This trait is only used on EditPage, CreatePage and ViewPage.
 |  Using it on ListPage will work, but probably cause CI errors.
 |
 */

namespace Moox\Core\Traits\Taxonomy;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Services\RelationService;
use Moox\Core\Traits\Relations\HasPagesRelations;

trait HasPagesTaxonomy
{
    use HasPagesRelations;

    protected function handleTaxonomies(): void
    {
        $this->handleInlineRelations();
    }

    protected function handleTaxonomiesBeforeFill(array &$data): void
    {
        $this->handleInlineRelationsBeforeFill($data);
    }

    protected function mutateFormDataBeforeFillWithTaxonomies(array $data): array
    {
        return $this->mutateFormDataBeforeFillWithInlineRelations($data);
    }

    protected function handleTaxonomiesBeforeSave(array $data): void
    {
        $this->handleInlineRelationsBeforeSave($data);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $model = static::getModel();

        if (! method_exists($model, 'getResourceName')) {
            return $data;
        }

        return app(RelationService::class)
            ->forResource($model::getResourceName())
            ->applyBelongsToCreatePrefill($data);
    }

    protected function getTaxonomyAttributes(): array
    {
        return $this->getInlineRelationAttributes();
    }

    protected function loadTaxonomyData(array $data): array
    {
        return $this->loadInlineRelationData($data);
    }

    protected function saveTaxonomyData(array $data): void
    {
        $this->saveInlineRelationData($data);
    }

    protected function saveTaxonomyDataForRecord(Model $record, array $data): void
    {
        $this->saveInlineRelationDataForRecord($record, $data);
    }

    protected function refreshTaxonomyFormData(): void
    {
        $this->refreshInlineRelationFormData();
    }

    protected function getRelatedTaxonomyIds(string $relationshipName): array
    {
        return $this->getRelatedInlineRelationIds($relationshipName);
    }
}
