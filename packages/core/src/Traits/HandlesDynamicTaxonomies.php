<?php

namespace Moox\Core\Traits;

use Filament\Forms\Components\Select;
use Illuminate\Support\Arr;
use Moox\Core\Services\TaxonomyService;

trait HandlesDynamicTaxonomies
{
    protected function getTaxonomyService(): TaxonomyService
    {
        return app(TaxonomyService::class);
    }

    protected function handleTaxonomies(): void
    {
        $record = $this->record;
        $data = $this->data;

        foreach ($this->getTaxonomyService()->getTaxonomies() as $taxonomy => $settings) {
            if (isset($data[$taxonomy])) {
                $tagIds = collect($data[$taxonomy])->map(function ($item) {
                    return is_array($item) ? $item['id'] : $item;
                })->toArray();
                $record->$taxonomy()->sync($tagIds);
            }
        }
    }

    protected function mutateFormDataBeforeFillWithTaxonomies(array $data): array
    {
        foreach ($this->getTaxonomyService()->getTaxonomies() as $taxonomy => $settings) {
            $taxonomyModel = app($settings['model']);
            $taxonomyTable = $taxonomyModel->getTable();

            $data[$taxonomy] = $this->record->$taxonomy()->pluck("{$taxonomyTable}.id")->toArray();
        }

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
