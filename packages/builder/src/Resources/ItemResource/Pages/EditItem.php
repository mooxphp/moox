<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Moox\Builder\Resources\ItemResource;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }

    public function refreshFormData(array $attributes = []): void
    {
        parent::refreshFormData($attributes);

        // Additional refresh logic if needed
        foreach (config('builder.taxonomies', []) as $taxonomy => $settings) {
            $taxonomyModel = app($settings['model']);
            $taxonomyTable = $taxonomyModel->getTable();

            $this->data[$taxonomy] = $this->record->$taxonomy()->pluck("{$taxonomyTable}.id")->toArray();
        }
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->handleTaxonomies();
        $this->refreshFormData();
    }

    protected function handleTaxonomies(): void
    {
        $record = $this->record;
        $data = $this->data;

        foreach (config('builder.taxonomies', []) as $taxonomy => $settings) {
            if (isset($data[$taxonomy])) {
                $tagIds = collect($data[$taxonomy])->map(function ($item) {
                    return is_array($item) ? $item['id'] : $item;
                })->toArray();
                Log::info("Syncing taxonomy: $taxonomy", ['data' => $tagIds]);
                $record->$taxonomy()->sync($tagIds);
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (config('builder.taxonomies', []) as $taxonomy => $settings) {
            $taxonomyModel = app($settings['model']);
            $taxonomyTable = $taxonomyModel->getTable();

            $data[$taxonomy] = $this->record->$taxonomy()->pluck("{$taxonomyTable}.id")->toArray();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
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
                    $taxonomies = config('builder.taxonomies', []);
                    foreach ($taxonomies as $taxonomy => $settings) {
                        if ($statePath === "data.{$taxonomy}") {
                            $modelClass = $settings['model'];
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
}
