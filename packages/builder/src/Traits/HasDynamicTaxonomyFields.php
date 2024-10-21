<?php

namespace Moox\Builder\Traits;

use Filament\Forms\Components\Select;
use Moox\Tag\Forms\TaxonomyCreateForm;

trait HasDynamicTaxonomyFields
{
    public static function getTaxonomyFields(): array
    {
        return collect(config('builder.taxonomies', []))
            ->map(function ($settings, $taxonomy) {
                return static::createTaxonomyField($taxonomy, $settings);
            })
            ->toArray();
    }

    protected static function createTaxonomyField(string $taxonomy, array $settings): Select
    {
        $modelClass = $settings['model'] ?? null;

        if (! $modelClass || ! class_exists($modelClass)) {
            throw new \InvalidArgumentException("Invalid model class for taxonomy: $taxonomy");
        }

        return Select::make($taxonomy)
            ->multiple()
            ->options(fn () => app($modelClass)::pluck('title', 'id')->toArray())
            ->getSearchResultsUsing(
                fn (string $search) => app($modelClass)::where('title', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('title', 'id')
                    ->toArray()
            )
            ->default(fn ($record) => $record ? $record->$taxonomy()->pluck('id')->toArray() : [])
            ->createOptionForm(TaxonomyCreateForm::getSchema())
            ->createOptionUsing(function (array $data, callable $set) use ($modelClass, $taxonomy) {
                $newTag = app($modelClass)::create($data);

                $set($taxonomy, function ($state) use ($newTag) {
                    $state = is_array($state) ? $state : [];
                    $state[] = $newTag->id;

                    return array_unique($state);
                });

                return $newTag->id;
            })
            ->preload()
            ->searchable();
    }
}
