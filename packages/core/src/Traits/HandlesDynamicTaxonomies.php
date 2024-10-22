<?php

namespace Moox\Core\Traits;

use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Moox\Core\Services\TaxonomyService;

trait HandlesDynamicTaxonomies
{
    protected function getTaxonomyService(): TaxonomyService
    {
        $service = app(TaxonomyService::class);
        $service->setCurrentResource($this->getResourceName());

        return $service;
    }

    protected function getResourceName(): string
    {
        return static::$resource::getResourceName();
    }

    protected function handleTaxonomies(): void
    {
        $record = $this->record;
        $data = $this->data;

        foreach ($this->getTaxonomies() as $taxonomy => $settings) {
            if (isset($data[$taxonomy])) {
                $tagIds = collect($data[$taxonomy])->map(function ($item) {
                    return is_array($item) ? $item['id'] : $item;
                })->toArray();
                $record->$taxonomy()->sync($tagIds);
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->mutateFormDataBeforeFillWithTaxonomies($data);
    }

    protected function mutateFormDataBeforeFillWithTaxonomies(array $data): array
    {
        foreach ($this->getTaxonomies() as $taxonomy => $settings) {
            $taxonomyModel = app($this->getTaxonomyModel($taxonomy));
            $taxonomyTable = $taxonomyModel->getTable();

            $data[$taxonomy] = $this->record->$taxonomy()->pluck("{$taxonomyTable}.id")->toArray();
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
                        if ($statePath === "data.{$taxonomy}") {
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
}
