<?php

namespace Moox\Core\Traits\Taxonomy;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Core\Forms\TaxonomyCreateForm as CoreTaxonomyCreateForm;
use Moox\Core\Services\TaxonomyService;

trait HasResourceTaxonomy
{
    protected static function getTaxonomyService(): TaxonomyService
    {
        $service = app(TaxonomyService::class);
        $service->setCurrentResource(static::getResourceName());

        return $service;
    }

    protected static function getResourceName(): string
    {
        return static::getModel()::getResourceName();
    }

    public static function getTaxonomyFields(): array
    {
        $taxonomyService = static::getTaxonomyService();

        return collect($taxonomyService->getTaxonomies())
            ->map(fn ($settings, $taxonomy): Select|SelectTree => static::createTaxonomyField($taxonomy, $settings, $taxonomyService))
            ->toArray();
    }

    protected static function createTaxonomyField(string $taxonomy, array $settings, TaxonomyService $taxonomyService): Select|SelectTree
    {
        $modelClass = $taxonomyService->getTaxonomyModel($taxonomy);
        $taxonomyService->validateTaxonomy($taxonomy);
        $isHierarchical = $settings['hierarchical'] ?? false;

        $createFormClass = $settings['createForm'] ?? CoreTaxonomyCreateForm::class;

        $commonConfig = [
            'label' => $settings['label'] ?? ucfirst($taxonomy),
            'searchable' => true,
            'createOptionForm' => $createFormClass::getSchema(),
            'createOptionUsing' => function (array $data) use ($modelClass) {
                $validator = validator($data, [
                    'title' => ['required', 'string', 'max:255'],
                    'slug' => ['required', 'string', 'max:255'],
                ]);

                if ($validator->fails()) {
                    return $validator->errors()->first();
                }

                $model = app($modelClass);

                // Check if model is translatable
                if (method_exists($model, 'createWithTranslations')) {
                    $locale = app()->getLocale();
                    $translations = [
                        $locale => [
                            'title' => $data['title'],
                            'slug' => $data['slug'],
                        ],
                    ];
                    $newTaxonomy = $model::createWithTranslations([], $translations);
                } else {
                    // Handle non-translatable models
                    $newTaxonomy = $model::create([
                        'title' => $data['title'],
                        'slug' => $data['slug'],
                    ]);
                }

                return $newTaxonomy->id;
            },
        ];

        if ($isHierarchical) {
            return SelectTree::make($taxonomy)
                ->relationship(
                    relationship: $taxonomy,
                    titleAttribute: 'title',
                    parentAttribute: 'parent_id'
                )
                ->enableBranchNode()
                ->searchable()
                ->createOptionForm($commonConfig['createOptionForm'])
                ->createOptionUsing($commonConfig['createOptionUsing'])
                ->label($commonConfig['label']);
        }

        return Select::make($taxonomy)
            ->multiple()
            ->options(fn () => app($modelClass)::get()->mapWithKeys(fn ($item) => [$item->id => $item->title])->toArray())
            ->getSearchResultsUsing(
                fn (string $search) => app($modelClass)::query()
                    ->when(method_exists($modelClass, 'with'), fn ($query) => $query->with('translations'))
                    ->when(method_exists($modelClass, 'whereHas'), function ($query) use ($search) {
                        $query->whereHas('translations', function ($q) use ($search) {
                            $q->where('title', 'like', sprintf('%%%s%%', $search))
                                ->where('locale', app()->getLocale());
                        });
                    }, function ($query) use ($search) {
                        $query->where('title', 'like', sprintf('%%%s%%', $search));
                    })
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(function ($item) {
                        if (method_exists($item, 'translate')) {
                            $locale = app()->getLocale();
                            $translation = $item->translate($locale);

                            return [$item->id => $translation ? $translation->title : 'ID: '.$item->id];
                        }

                        return [$item->id => $item->title];
                    })
                    ->toArray()
            )
            ->createOptionForm($commonConfig['createOptionForm'])
            ->createOptionUsing($commonConfig['createOptionUsing'])
            ->searchable()
            ->label($commonConfig['label']);
    }

    public static function getTaxonomyFilters(): array
    {
        $taxonomyService = static::getTaxonomyService();
        $taxonomies = $taxonomyService->getTaxonomies();
        $resourceModel = static::getModel();
        $resourceTable = app($resourceModel)->getTable();

        return collect($taxonomies)->map(function ($settings, $taxonomy) use ($taxonomyService, $resourceTable): SelectFilter {
            $taxonomyModel = $taxonomyService->getTaxonomyModel($taxonomy);
            $pivotTable = $taxonomyService->getTaxonomyTable($taxonomy);
            $foreignKey = $taxonomyService->getTaxonomyForeignKey($taxonomy);
            $relatedKey = $taxonomyService->getTaxonomyRelatedKey($taxonomy);
            $taxonomyTable = app($taxonomyModel)->getTable();

            return SelectFilter::make($taxonomy)
                ->label($settings['label'] ?? ucfirst($taxonomy))
                ->multiple()
                ->options(fn () => app($taxonomyModel)::query()
                    ->when(method_exists($taxonomyModel, 'with'), fn ($query) => $query->with('translations'))
                    ->get()
                    ->mapWithKeys(function ($item) {
                        if (method_exists($item, 'translate')) {
                            $locale = app()->getLocale();
                            $translation = $item->translate($locale);

                            return [$item->id => $translation ? $translation->title : 'ID: '.$item->id];
                        }

                        return [$item->id => $item->title];
                    })
                    ->toArray()
                )
                ->query(function (Builder $query, array $data) use ($pivotTable, $foreignKey, $relatedKey, $resourceTable): void {
                    $selectedIds = $data['values'] ?? [];
                    if (! empty($selectedIds)) {
                        $query->whereExists(function ($subQuery) use ($pivotTable, $foreignKey, $relatedKey, $resourceTable, $selectedIds): void {
                            $subQuery->select(DB::raw(1))
                                ->from($pivotTable)
                                ->whereColumn(sprintf('%s.%s', $pivotTable, $foreignKey), $resourceTable.'.id')
                                ->whereIn(sprintf('%s.%s', $pivotTable, $relatedKey), $selectedIds);
                        });
                    }
                });
        })->toArray();
    }

    protected static function getTaxonomyColumns(): array
    {
        $taxonomyService = static::getTaxonomyService();
        $taxonomies = $taxonomyService->getTaxonomies();

        return collect($taxonomies)->map(fn ($settings, $taxonomy): TagsColumn => TagsColumn::make($taxonomy)
            ->label($settings['label'] ?? ucfirst((string) $taxonomy))
            ->getStateUsing(function ($record) use ($taxonomy, $taxonomyService, $settings) {
                $relationshipName = $settings['relationship'] ?? $taxonomy;
                $table = $taxonomyService->getTaxonomyTable($taxonomy);
                $foreignKey = $taxonomyService->getTaxonomyForeignKey($taxonomy);
                $relatedKey = $taxonomyService->getTaxonomyRelatedKey($taxonomy);
                $modelClass = $taxonomyService->getTaxonomyModel($taxonomy);

                $model = app($modelClass);
                $modelTable = $model->getTable();

                return DB::table($table)
                    ->join($modelTable, sprintf('%s.%s', $table, $relatedKey), '=', $modelTable.'.id')
                    ->where(sprintf('%s.%s', $table, $foreignKey), $record->id)
                    ->when(method_exists($model, 'with'), function ($query) use ($modelTable, $modelClass) {
                        return $query->join('translations', function ($join) use ($modelTable, $modelClass) {
                            $join->on('translations.translatable_id', '=', $modelTable.'.id')
                                ->where('translations.translatable_type', '=', $modelClass)
                                ->where('translations.locale', '=', app()->getLocale());
                        })->pluck('translations.title');
                    }, function ($query) use ($modelTable) {
                        return $query->pluck($modelTable.'.title');
                    })
                    ->toArray();
            })
            ->toggleable(isToggledHiddenByDefault: true)
            ->separator(',')
            ->searchable())->toArray();
    }

    protected static function handleTaxonomies(Model $record, array $data): void
    {
        $taxonomyService = static::getTaxonomyService();
        foreach (array_keys($taxonomyService->getTaxonomies()) as $taxonomy) {
            if (isset($data[$taxonomy])) {
                $relationshipName = $taxonomyService->getTaxonomyRelationship($taxonomy);

                // Use the relationship name from the taxonomy service
                if (method_exists($record, $relationshipName)) {
                    $record->$relationshipName()->sync($data[$taxonomy]);
                }
            }
        }
    }

    protected static function addTaxonomyRelationsToQuery(Builder $query): Builder
    {
        $taxonomyService = static::getTaxonomyService();
        $taxonomies = $taxonomyService->getTaxonomies();

        foreach (array_keys($taxonomies) as $taxonomy) {
            $relationshipName = $taxonomyService->getTaxonomyRelationship($taxonomy);

            if (method_exists($query->getModel(), $relationshipName)) {
                $query->with($relationshipName);
            }
        }

        return $query;
    }
}
