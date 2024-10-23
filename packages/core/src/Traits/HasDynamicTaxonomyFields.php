<?php

namespace Moox\Core\Traits;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
            ->map(fn ($settings, $taxonomy) => static::createTaxonomyField($taxonomy, $settings, $taxonomyService))
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
                ->createOptionUsing(fn (array $data) => app($modelClass)::create($data));
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
                $set($taxonomy, fn ($state) => array_unique(array_merge(is_array($state) ? $state : [], [$newTag->id])));

                return $newTag->id;
            })
            ->preload()
            ->searchable()
            ->label($settings['label'] ?? ucfirst($taxonomy));
    }

    public static function getTaxonomyFilters(): array
    {
        $taxonomyService = static::getTaxonomyService();
        $taxonomies = $taxonomyService->getTaxonomies();
        $resourceModel = static::getModel();
        $resourceTable = app($resourceModel)->getTable();

        return collect($taxonomies)->map(function ($settings, $taxonomy) use ($taxonomyService, $resourceTable) {
            $taxonomyModel = $taxonomyService->getTaxonomyModel($taxonomy);
            $pivotTable = $taxonomyService->getTaxonomyTable($taxonomy);
            $foreignKey = $taxonomyService->getTaxonomyForeignKey($taxonomy);
            $relatedKey = $taxonomyService->getTaxonomyRelatedKey($taxonomy);
            $taxonomyTable = app($taxonomyModel)->getTable();

            return SelectFilter::make($taxonomy)
                ->label($settings['label'] ?? ucfirst($taxonomy))
                ->multiple()
                ->options(fn () => $taxonomyModel::pluck('title', 'id')->toArray())
                ->query(function (Builder $query, array $data) use ($pivotTable, $foreignKey, $relatedKey, $resourceTable) {
                    $selectedIds = $data['values'] ?? [];
                    if (! empty($selectedIds)) {
                        $query->whereExists(function ($subQuery) use ($pivotTable, $foreignKey, $relatedKey, $resourceTable, $selectedIds) {
                            $subQuery->select(DB::raw(1))
                                ->from($pivotTable)
                                ->whereColumn("{$pivotTable}.{$foreignKey}", "{$resourceTable}.id")
                                ->whereIn("{$pivotTable}.{$relatedKey}", $selectedIds);
                        });
                    }
                });
        })->toArray();
    }

    protected static function getTaxonomyColumns(): array
    {
        $taxonomyService = static::getTaxonomyService();
        $taxonomies = $taxonomyService->getTaxonomies();

        return collect($taxonomies)->map(function ($settings, $taxonomy) use ($taxonomyService) {
            return TagsColumn::make($taxonomy)
                ->label($settings['label'] ?? ucfirst($taxonomy))
                ->getStateUsing(function ($record) use ($taxonomy, $taxonomyService, $settings) {
                    $relationshipName = $settings['relationship'] ?? $taxonomy;
                    $table = $taxonomyService->getTaxonomyTable($taxonomy);
                    $foreignKey = $taxonomyService->getTaxonomyForeignKey($taxonomy);
                    $relatedKey = $taxonomyService->getTaxonomyRelatedKey($taxonomy);
                    $modelClass = $taxonomyService->getTaxonomyModel($taxonomy);
                    $model = app($modelClass);
                    $modelTable = $model->getTable();

                    return DB::table($table)
                        ->join($modelTable, "{$table}.{$relatedKey}", '=', "{$modelTable}.id")
                        ->where("{$table}.{$foreignKey}", $record->id)
                        ->pluck("{$modelTable}.title")
                        ->toArray();
                })
                ->toggleable(isToggledHiddenByDefault: true)
                ->separator(',')
                ->searchable();
        })->toArray();
    }

    protected static function handleTaxonomies(Model $record, array $data): void
    {
        $taxonomyService = static::getTaxonomyService();
        foreach ($taxonomyService->getTaxonomies() as $taxonomy => $settings) {
            if (isset($data[$taxonomy])) {
                $record->$taxonomy()->sync($data[$taxonomy]);
            }
        }
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $taxonomyService = static::getTaxonomyService();
        $taxonomies = $taxonomyService->getTaxonomies();

        foreach ($taxonomies as $taxonomy => $settings) {
            $relationshipName = $taxonomyService->getTaxonomyRelationship($taxonomy);
            $query->with($relationshipName);
        }

        return $query;
    }
}
