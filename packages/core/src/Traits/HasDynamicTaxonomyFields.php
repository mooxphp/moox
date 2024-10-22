<?php

namespace Moox\Core\Traits;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Moox\Core\Services\TaxonomyService;
use Moox\Tag\Forms\TaxonomyCreateForm;

trait HasDynamicTaxonomyFields
{
    protected static function getTaxonomyService(): TaxonomyService
    {
        $service = app(TaxonomyService::class);
        $service->setCurrentResource(static::getResourceName());

        return $service;
    }

    protected static function getResourceName(): string
    {
        return strtolower(class_basename(static::class));
    }

    public static function getTaxonomyFields(): array
    {
        $taxonomyService = static::getTaxonomyService();

        return collect($taxonomyService->getTaxonomies())
            ->map(function ($settings, $taxonomy) use ($taxonomyService) {
                return static::createTaxonomyField($taxonomy, $settings, $taxonomyService);
            })
            ->toArray();
    }

    protected static function createTaxonomyField(string $taxonomy, array $settings, TaxonomyService $taxonomyService): Select|SelectTree
    {
        $modelClass = $taxonomyService->getTaxonomyModel($taxonomy);

        $taxonomyService->validateTaxonomy($taxonomy);

        $isHierarchical = $settings['hierarchical'] ?? false;

        if ($isHierarchical) {
            return SelectTree::make($taxonomy)
                ->relationship(
                    relationship: $taxonomy,
                    titleAttribute: 'title',
                    parentAttribute: 'parent_id'
                )
                ->label($settings['label'] ?? ucfirst($taxonomy))
                ->searchable()
                ->enableBranchNode()
                ->createOptionForm(TaxonomyCreateForm::getSchema())
                ->createOptionUsing(function (array $data) use ($modelClass) {
                    return app($modelClass)::create($data);
                });
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
            ->searchable()
            ->label($settings['label'] ?? ucfirst($taxonomy));
    }
}
