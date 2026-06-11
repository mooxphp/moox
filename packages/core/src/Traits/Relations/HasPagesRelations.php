<?php

declare(strict_types=1);

/*
 |  Attention!
 |
 |  This trait is only used on EditPage, CreatePage and ViewPage.
 |  Using it on ListPage will work, but probably cause CI errors.
 |
 */

namespace Moox\Core\Traits\Relations;

use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Moox\Core\Services\RelationService;

trait HasPagesRelations
{
    use HasRelationService;

    protected function handleInlineRelations(): void
    {
        $record = $this->record;
        $data = $this->data;

        foreach ($this->inlineRelationKeys() as $key) {
            if (isset($data[$key])) {
                $tagIds = collect($data[$key])->map(fn ($item): mixed => is_array($item) ? $item['id'] : $item)->toArray();
                $record->{$key}()->sync($tagIds);
            }
        }
    }

    protected function handleInlineRelationsBeforeFill(array &$data): void
    {
        $data = $this->loadInlineRelationData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFillWithInlineRelations(array $data): array
    {
        $service = $this->pageRelationService();

        foreach ($service->inlineRelationConfigs() as $key => $settings) {
            $relationshipName = $settings['relationship'] ?? $key;
            $data[$key] = $this->getRelatedInlineRelationIds($relationshipName);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleInlineRelationsBeforeSave(array $data): void
    {
        $this->saveInlineRelationData($data);
    }

    /**
     * @return list<string>
     */
    protected function getInlineRelationAttributes(): array
    {
        return $this->inlineRelationKeys();
    }

    /**
     * @return array<int|string, string>
     */
    public function getFormSelectOptionLabels(string $statePath): array
    {
        $select = Arr::get($this->getCachedSchemas(), $statePath);

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
                    foreach ($this->inlineRelationKeys() as $key) {
                        if ($statePath === 'data.'.$key) {
                            $modelClass = $this->pageRelationService()->relatedModel($key);
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

    protected function afterCreate(): void
    {
        $this->record->refresh();
        $this->handleInlineRelations();
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->handleInlineRelations();
        $this->refreshInlineRelationFormData();
    }

    protected function refreshInlineRelationFormData(): void
    {
        if ($this instanceof EditRecord) {
            $this->refreshFormData($this->getInlineRelationAttributes());
        }
    }

    public function refreshFormData(array $attributes = []): void
    {
        if (method_exists($this, 'fillForm')) {
            $this->fillForm();
        }
    }

    protected function getRelatedInlineRelationIds(string $relationshipName): array
    {
        $record = $this->record;
        if ($record === null || ! method_exists($record, $relationshipName)) {
            return [];
        }

        $relation = $record->{$relationshipName}();

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

        $this->handleInlineRelations();

        return $record;
    }

    public function getModel(): string
    {
        return static::getResource()::getModel();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function loadInlineRelationData(array $data): array
    {
        $record = $this->getRecord();
        if ($record === null || ! method_exists($record, 'getResourceName')) {
            return $data;
        }

        try {
            $service = $this->pageRelationService();

            foreach ($service->inlineRelationConfigs() as $key => $settings) {
                if (! $record->exists) {
                    $data[$key] = [];

                    continue;
                }

                $table = $service->pivotTable($key);
                $foreignKey = $service->foreignKey($key);
                $relatedKey = $service->relatedKey($key);
                $modelClass = $service->relatedModel($key);

                $model = app($modelClass);
                $modelTable = $model->getTable();

                $data[$key] = DB::table($table)
                    ->join($modelTable, sprintf('%s.%s', $table, $relatedKey), '=', $modelTable.'.id')
                    ->where(sprintf('%s.%s', $table, $foreignKey), $record->getKey())
                    ->pluck($modelTable.'.id')
                    ->toArray();
            }
        } catch (\Exception) {
            // Continue without inline relations when configuration is missing.
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveInlineRelationData(array $data): void
    {
        $record = $this->getRecord();
        if ($record === null || ! method_exists($record, 'getResourceName')) {
            return;
        }

        try {
            foreach ($this->inlineRelationKeys() as $key) {
                if (! isset($data[$key])) {
                    continue;
                }

                $tagIds = collect($data[$key])->map(fn ($item): mixed => is_array($item) ? $item['id'] : $item)->toArray();

                if (method_exists($record, 'syncRelation')) {
                    $record->syncRelation($key, $tagIds);
                }
            }
        } catch (\Exception) {
            // Continue without inline relations when configuration is missing.
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveInlineRelationDataForRecord(Model $record, array $data): void
    {
        if (! method_exists($record, 'getResourceName')) {
            return;
        }

        try {
            foreach (array_keys(app(RelationService::class)->forResource($record->getResourceName())->inlineRelationConfigs()) as $key) {
                if (! isset($data[$key])) {
                    continue;
                }

                $tagIds = collect($data[$key])->map(fn ($item): mixed => is_array($item) ? $item['id'] : $item)->toArray();

                if (method_exists($record, 'syncRelation')) {
                    $record->syncRelation($key, $tagIds);
                }
            }
        } catch (\Exception) {
            // Continue without inline relations when configuration is missing.
        }
    }

    protected function pageRelationService(): RelationService
    {
        $modelClass = static::getResource()::getModel();

        if (method_exists($modelClass, 'getResourceName')) {
            return app(RelationService::class)->forResource($modelClass::getResourceName());
        }

        $record = $this->getRecord();

        if ($record !== null && method_exists($record, 'getResourceName')) {
            return app(RelationService::class)->forResource($record->getResourceName());
        }

        throw new \LogicException(sprintf('Cannot resolve relation service for page %s.', static::class));
    }

    /**
     * @return list<string>
     */
    protected function inlineRelationKeys(): array
    {
        try {
            return array_keys($this->pageRelationService()->inlineRelationConfigs());
        } catch (\Exception) {
            return [];
        }
    }
}
