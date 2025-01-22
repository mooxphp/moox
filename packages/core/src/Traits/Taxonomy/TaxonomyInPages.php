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

trait TaxonomyInPages
{
    protected function getTaxonomyService(): TaxonomyService
    {
        $service = app(TaxonomyService::class);
        $service->setCurrentResource($this->getResourceName());

        return $service;
    }

    protected function getResourceName(): string
    {
        $model = static::getModel();

        return method_exists($model, 'getResourceName') ? $model::getResourceName() : class_basename($model);
    }

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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! $this->record) {
            return $data;
        }

        $taxonomyService = $this->getTaxonomyService();
        $taxonomies = $taxonomyService->getTaxonomies();

        foreach ($taxonomies as $taxonomy => $settings) {
            $table = $taxonomyService->getTaxonomyTable($taxonomy);
            $foreignKey = $taxonomyService->getTaxonomyForeignKey($taxonomy);
            $relatedKey = $taxonomyService->getTaxonomyRelatedKey($taxonomy);
            $modelClass = $taxonomyService->getTaxonomyModel($taxonomy);

            $model = app($modelClass);
            $modelTable = $model->getTable();

            $tags = DB::table($table)
                ->join($modelTable, sprintf('%s.%s', $table, $relatedKey), '=', $modelTable.'.id')
                ->where(sprintf('%s.%s', $table, $foreignKey), $this->record->getKey())
                ->pluck($modelTable.'.id')
                ->toArray();

            $data[$taxonomy] = $tags;
        }

        return $data;
    }

    protected function mutateFormDataBeforeFillWithTaxonomies(array $data): array
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();

        foreach ($taxonomies as $taxonomy => $settings) {
            $relationshipName = $settings['relationship'] ?? $taxonomy;
            $data[$taxonomy] = $this->getRelatedTaxonomyIds($relationshipName);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function getTaxonomyAttributes(): array
    {
        return array_keys($this->getTaxonomies());
    }

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

    protected function getTaxonomies(): array
    {
        return $this->getTaxonomyService()->getTaxonomies();
    }

    protected function getTaxonomyModel(string $taxonomy): ?string
    {
        return $this->getTaxonomyService()->getTaxonomyModel($taxonomy);
    }

    protected function validateTaxonomy(string $taxonomy): void
    {
        $this->getTaxonomyService()->validateTaxonomy($taxonomy);
    }

    protected function getTaxonomyRelationship(string $taxonomy): string
    {
        return $this->getTaxonomyService()->getTaxonomyRelationship($taxonomy);
    }

    protected function getTaxonomyTable(string $taxonomy): string
    {
        return $this->getTaxonomyService()->getTaxonomyTable($taxonomy);
    }

    protected function getTaxonomyForeignKey(string $taxonomy): string
    {
        return $this->getTaxonomyService()->getTaxonomyForeignKey($taxonomy);
    }

    protected function getTaxonomyRelatedKey(string $taxonomy): string
    {
        return $this->getTaxonomyService()->getTaxonomyRelatedKey($taxonomy);
    }

    protected function afterCreate(): void
    {
        $this->record->refresh();
        $this->handleTaxonomies();
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->handleTaxonomies();
        $this->refreshTaxonomyFormData();
    }

    protected function refreshTaxonomyFormData(): void
    {
        if ($this instanceof EditRecord) {
            $this->refreshFormData($this->getTaxonomyAttributes());
        }
    }

    public function refreshFormData(array $attributes = []): void
    {
        if (method_exists($this, 'fillForm')) {
            $this->fillForm();
        }
    }

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

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->record ? $this->record->toArray() : [];

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        $this->handleTaxonomies();

        return $record;
    }

    public function getModel(): string
    {
        return static::getResource()::getModel();
    }
}
