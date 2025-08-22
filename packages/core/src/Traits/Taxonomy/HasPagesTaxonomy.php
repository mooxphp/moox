<?php

/*
 |  Attention!
 |
 |  This trait is only used on EditPage, CreatePage and ViewPage.
 |  Using it on ListPage will work, but probably cause CI errors.
 |
 */

namespace Moox\Core\Traits\Taxonomy;

use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Moox\Core\Services\TaxonomyService;

trait HasPagesTaxonomy
{
    use HasTaxonomyService;

    /**
     * Handle the taxonomies.
     */
    protected function handleTaxonomies(): void
    {
        $record = $this->record;
        $data = $this->data;

        foreach ($this->getTaxonomies() as $taxonomy => $settings) {
            if (isset($data[$taxonomy])) {
                $tagIds = collect($data[$taxonomy])->map(fn ($item): mixed => is_array($item) ? $item['id'] : $item)->toArray();
                $record->$taxonomy()->sync($tagIds);
            }
        }
    }

    /**
     * Hook method that should be called in mutateFormDataBeforeFill.
     */
    protected function handleTaxonomiesBeforeFill(array &$data): void
    {
        $data = $this->loadTaxonomyData($data);
    }

    /**
     * Mutate the form data before fill with taxonomies.
     */
    protected function mutateFormDataBeforeFillWithTaxonomies(array $data): array
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();

        foreach ($taxonomies as $taxonomy => $settings) {
            $relationshipName = $settings['relationship'] ?? $taxonomy;
            $data[$taxonomy] = $this->getRelatedTaxonomyIds($relationshipName);
        }

        return $data;
    }

    /**
     * Hook method that should be called in mutateFormDataBeforeSave.
     */
    protected function handleTaxonomiesBeforeSave(array $data): void
    {
        $this->saveTaxonomyData($data);
    }

    /**
     * Mutate the form data before create.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    /**
     * Get the taxonomy attributes.
     */
    protected function getTaxonomyAttributes(): array
    {
        return array_keys($this->getTaxonomies());
    }

    /**
     * Get the form select option labels.
     */
    public function getFormSelectOptionLabels(string $statePath): array
    {
        $select = Arr::get($this->getCachedForms(), $statePath);

        if (! $select instanceof Select) {
            return [];
        }

        $options = $select->getOptions();

        if (is_callable($options)) {
            $options = $options();
        }

        $values = data_get($this->data, $statePath) ?? [];

        if (! is_array($values)) {
            $values = [$values];
        }

        $labels = [];
        foreach ($values as $value) {
            if (is_scalar($value) && isset($options[$value])) {
                $labels[$value] = $options[$value];
            } elseif (is_array($value) && isset($value['id'], $value['title'])) {
                $labels[$value['id']] = $value['title'];
            } elseif (is_numeric($value) || is_string($value)) {
                $label = $options[$value] ?? null;

                if ($label === null) {
                    $taxonomies = $this->getTaxonomies();
                    foreach ($taxonomies as $taxonomy => $settings) {
                        if ($statePath === 'data.'.$taxonomy) {
                            $modelClass = $this->getTaxonomyModel($taxonomy);
                            $model = app($modelClass)::find($value);
                            if ($model) {
                                $label = $model->title;
                                break;
                            }
                        }
                    }
                }

                if ($label !== null) {
                    $labels[$value] = $label;
                }
            }
        }

        return $labels;
    }

    /**
     * Get the taxonomies.
     */
    protected function getTaxonomies(): array
    {
        return $this->getTaxonomyService()->getTaxonomies();
    }

    /**
     * Get the taxonomy model.
     */
    protected function getTaxonomyModel(string $taxonomy): ?string
    {
        return $this->getTaxonomyService()->getTaxonomyModel($taxonomy);
    }

    /**
     * Validate the taxonomy.
     */
    protected function validateTaxonomy(string $taxonomy): void
    {
        $this->getTaxonomyService()->validateTaxonomy($taxonomy);
    }

    /**
     * Get the taxonomy relationship.
     */
    protected function getTaxonomyRelationship(string $taxonomy): string
    {
        return $this->getTaxonomyService()->getTaxonomyRelationship($taxonomy);
    }

    /**
     * Get the taxonomy table.
     */
    protected function getTaxonomyTable(string $taxonomy): string
    {
        return $this->getTaxonomyService()->getTaxonomyTable($taxonomy);
    }

    /**
     * Get the taxonomy foreign key.
     */
    protected function getTaxonomyForeignKey(string $taxonomy): string
    {
        return $this->getTaxonomyService()->getTaxonomyForeignKey($taxonomy);
    }

    /**
     * Get the taxonomy related key.
     */
    protected function getTaxonomyRelatedKey(string $taxonomy): string
    {
        return $this->getTaxonomyService()->getTaxonomyRelatedKey($taxonomy);
    }

    /**
     * After create.
     */
    protected function afterCreate(): void
    {
        $this->record->refresh();
        $this->handleTaxonomies();
    }

    /**
     * After save.
     */
    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->handleTaxonomies();
        $this->refreshTaxonomyFormData();
    }

    /**
     * Refresh the taxonomy form data.
     */
    protected function refreshTaxonomyFormData(): void
    {
        if ($this instanceof EditRecord) {
            $this->refreshFormData($this->getTaxonomyAttributes());
        }
    }

    /**
     * Refresh the form data.
     */
    public function refreshFormData(array $attributes = []): void
    {
        /** @phpstan-ignore-next-line */
        if (method_exists($this, 'fillForm')) {
            $this->fillForm();
        }
    }

    /**
     * Get the related taxonomy ids.
     */
    protected function getRelatedTaxonomyIds(string $relationshipName): array
    {
        if (! method_exists($this->record, $relationshipName)) {
            return [];
        }

        $relation = $this->record->$relationshipName();

        if (! $relation instanceof MorphToMany) {
            return [];
        }

        return $relation->pluck('id')->toArray();
    }

    /**
     * Fill the form.
     */
    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->record ? $this->record->toArray() : [];

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    /**
     * Handle the record update.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        $this->handleTaxonomies();

        return $record;
    }

    /**
     * Get the model.
     */
    public function getModel(): string
    {
        return static::getResource()::getModel();
    }

    /**
     * Load taxonomy data for the record.
     */
    protected function loadTaxonomyData(array $data): array
    {
        $record = $this->getRecord();

        if (! $record || ! method_exists($record, 'getResourceName')) {
            return $data;
        }

        try {
            $taxonomyService = app(TaxonomyService::class);
            $taxonomyService->setCurrentResource($record->getResourceName());
            $taxonomies = $taxonomyService->getTaxonomies();

            foreach ($taxonomies as $taxonomy => $settings) {
                // Only load taxonomy data if the record has an ID (exists in database)
                if (! $record->exists) {
                    $data[$taxonomy] = [];

                    continue;
                }

                $table = $taxonomyService->getTaxonomyTable($taxonomy);
                $foreignKey = $taxonomyService->getTaxonomyForeignKey($taxonomy);
                $relatedKey = $taxonomyService->getTaxonomyRelatedKey($taxonomy);
                $modelClass = $taxonomyService->getTaxonomyModel($taxonomy);

                $model = app($modelClass);
                $modelTable = $model->getTable();

                $tags = DB::table($table)
                    ->join($modelTable, sprintf('%s.%s', $table, $relatedKey), '=', $modelTable.'.id')
                    ->where(sprintf('%s.%s', $table, $foreignKey), $record->getKey())
                    ->pluck($modelTable.'.id')
                    ->toArray();

                // Ensure we always have a valid array
                $data[$taxonomy] = is_array($tags) ? $tags : [];
            }
        } catch (\Exception $e) {
            // If taxonomy service fails, just continue without taxonomies
        }

        return $data;
    }

    /**
     * Save taxonomy data for the record.
     */
    protected function saveTaxonomyData(array $data): void
    {
        $record = $this->getRecord();

        if (! $record || ! method_exists($record, 'getResourceName')) {
            return;
        }

        try {
            $taxonomyService = app(TaxonomyService::class);
            $taxonomyService->setCurrentResource($record->getResourceName());
            $taxonomies = $taxonomyService->getTaxonomies();

            foreach ($taxonomies as $taxonomy => $settings) {
                if (isset($data[$taxonomy])) {
                    $tagIds = collect($data[$taxonomy])->map(fn ($item): mixed => is_array($item) ? $item['id'] : $item)->toArray();

                    // Use the syncTaxonomy method from HasModelTaxonomy trait
                    if (method_exists($record, 'syncTaxonomy')) {
                        $record->syncTaxonomy($taxonomy, $tagIds);
                    }
                }
            }
        } catch (\Exception $e) {
            // If taxonomy service fails, just continue without taxonomies
        }
    }

    /**
     * Save taxonomy data for a specific record (for create pages).
     */
    protected function saveTaxonomyDataForRecord(Model $record, array $data): void
    {
        if (! $record || ! method_exists($record, 'getResourceName')) {
            return;
        }

        try {
            $taxonomyService = app(TaxonomyService::class);
            $taxonomyService->setCurrentResource($record->getResourceName());
            $taxonomies = $taxonomyService->getTaxonomies();

            foreach ($taxonomies as $taxonomy => $settings) {
                if (isset($data[$taxonomy])) {
                    $tagIds = collect($data[$taxonomy])->map(fn ($item): mixed => is_array($item) ? $item['id'] : $item)->toArray();

                    // Use the syncTaxonomy method from HasModelTaxonomy trait
                    if (method_exists($record, 'syncTaxonomy')) {
                        $record->syncTaxonomy($taxonomy, $tagIds);
                    }
                }
            }
        } catch (\Exception $e) {
            // If taxonomy service fails, just continue without taxonomies
        }
    }
}
