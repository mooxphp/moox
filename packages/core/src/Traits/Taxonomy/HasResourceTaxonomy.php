<?php

namespace Moox\Core\Traits\Taxonomy;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Category\Moox\Entities\Categories\Category\Forms\TaxonomyCreateForm as CoreTaxonomyCreateForm;
use Moox\Core\Services\TaxonomyService;
use Moox\Localization\Models\Localization;

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
            'createOptionUsing' => function (array $data, $livewire) use ($modelClass) {
                $defaultLocalization = Localization::where('is_default', true)->first();
                $mainLocale = $defaultLocalization?->locale_variant ?? config('app.locale', 'en');
                $currentLocale = $livewire->lang
                    ?? request()->query('lang')
                    ?? $mainLocale;

                if ($currentLocale !== $mainLocale) {
                    return __('core::core.taxonomy_creation_only_in_main_language');
                }
                $validator = validator($data, [
                    'title' => ['required', 'string', 'max:255'],
                    'slug' => ['required', 'string', 'max:255'],
                ]);

                if ($validator->fails()) {
                    return $validator->errors()->first();
                }

                $model = app($modelClass);

                if (method_exists($model, 'createTranslation')) {
                    $locale = $livewire->lang
                        ?? request()->query('lang')
                        ?? $mainLocale;

                    $translatableAttributes = property_exists($model, 'translatedAttributes') ? $model->translatedAttributes : [];
                    $translationData = array_intersect_key($data, array_flip($translatableAttributes));
                    $nonTranslatableData = array_diff_key($data, array_flip($translatableAttributes));

                    $fillableAttributes = $model->getFillable();
                    foreach ($fillableAttributes as $field) {
                        if (isset($data[$field]) && ! in_array($field, $translatableAttributes)) {
                            $nonTranslatableData[$field] = $data[$field];
                        }
                    }

                    $newTaxonomy = $model::create($nonTranslatableData);
                    $newTaxonomy->createTranslation($locale, $translationData);

                    $newTaxonomy->refresh();
                } else {
                    $newTaxonomy = $model::create($data);
                }

                Notification::make()
                    ->title(__('core::core.taxonomy_created_successfully'))
                    ->body(__('core::core.taxonomy_created_successfully_body'))
                    ->success()
                    ->send();

                return $newTaxonomy->id;
            },
        ];

        $defaultLocalization = Localization::where('is_default', true)->first();
        $mainLocale = $defaultLocalization?->locale_variant ?? config('app.locale', 'en');
        $currentLocale = request()->query('lang') ?? $mainLocale;
        $canCreate = $currentLocale === $mainLocale;

        if ($isHierarchical) {
            $selectTree = SelectTree::make($taxonomy)
                ->relationship(
                    relationship: $taxonomy,
                    titleAttribute: 'display_title',
                    parentAttribute: 'parent_id'
                )
                ->enableBranchNode()
                ->searchable()
                ->label($commonConfig['label']);

            if ($canCreate) {
                $selectTree->createOptionForm($commonConfig['createOptionForm'])
                    ->createOptionUsing($commonConfig['createOptionUsing']);
            }

            return $selectTree;
        }

        $select = Select::make($taxonomy)
            ->multiple()
            ->options(function () use ($modelClass) {
                $defaultLocalization = Localization::where('is_default', true)->first();
                $mainLocale = $defaultLocalization?->locale_variant ?? config('app.locale', 'en');
                $locale = request()->query('lang') ?? $mainLocale;

                return app($modelClass)::with('translations')->get()->mapWithKeys(function ($item) use ($locale, $mainLocale) {
                    if (method_exists($item, 'translations')) {
                        $translation = $item->translations()->where('locale', $locale)->first();
                        $isFallback = false;
                        if (! $translation || ! $translation->title) {
                            $mainTranslation = $item->translations()->where('locale', $mainLocale)->first();
                            $title = $mainTranslation && $mainTranslation->title ? $mainTranslation->title : 'ID: '.$item->id;
                            $isFallback = true;
                        } else {
                            $title = $translation->title;
                        }

                        if ($isFallback) {
                            $title = $title.' ('.$mainLocale.')';
                        }

                        return [$item->id => $title];
                    }

                    return [$item->id => $item->title];
                })->toArray();
            })
            ->getSearchResultsUsing(
                function (string $search) use ($modelClass) {
                    $defaultLocalization = Localization::where('is_default', true)->first();
                    $mainLocale = $defaultLocalization?->locale_variant ?? config('app.locale', 'en');
                    $locale = request()->query('lang') ?? $mainLocale;

                    return app($modelClass)::query()
                        ->when(method_exists($modelClass, 'with'), fn ($query) => $query->with('translations'))
                        ->when(method_exists($modelClass, 'whereHas'), function ($query) use ($search, $locale) {
                            $query->whereHas('translations', function ($q) use ($search, $locale) {
                                $q->where('title', 'like', sprintf('%%%s%%', $search))
                                    ->where('locale', $locale);
                            });
                        }, function ($query) use ($search) {
                            $query->where('title', 'like', sprintf('%%%s%%', $search));
                        })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function ($item) use ($locale, $mainLocale) {
                            if (method_exists($item, 'translate')) {
                                $translation = $item->translate($locale);
                                $isFallback = false;
                                if (! $translation || ! $translation->title) {
                                    $mainTranslation = $item->translate($mainLocale);
                                    $title = $mainTranslation && $mainTranslation->title ? $mainTranslation->title : 'ID: '.$item->id;
                                    $isFallback = true;
                                } else {
                                    $title = $translation->title;
                                }

                                if ($isFallback) {
                                    $title = $title.' ('.$mainLocale.')';
                                }

                                return [$item->id => $title];
                            }

                            return [$item->id => $item->title];
                        })
                        ->toArray();
                }
            )
            ->searchable()
            ->label($commonConfig['label']);

        if ($canCreate) {
            $select->createOptionForm($commonConfig['createOptionForm'])
                ->createOptionUsing($commonConfig['createOptionUsing']);
        }

        return $select;
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
                ->options(
                    function () use ($taxonomyModel, $pivotTable, $foreignKey, $relatedKey, $resourceTable) {
                        $defaultLocalization = Localization::where('is_default', true)->first();
                        $mainLocale = $defaultLocalization?->locale_variant ?? config('app.locale', 'en');
                        $locale = request()->query('lang') ?? $mainLocale;

                        $usedTaxonomyIds = DB::table($pivotTable)
                            ->join($resourceTable, $pivotTable.'.'.$foreignKey, '=', $resourceTable.'.id')
                            ->distinct()
                            ->pluck($pivotTable.'.'.$relatedKey)
                            ->filter()
                            ->toArray();

                        if (empty($usedTaxonomyIds)) {
                            return [];
                        }

                        return app($taxonomyModel)::query()
                            ->whereIn('id', $usedTaxonomyIds)
                            ->when(method_exists($taxonomyModel, 'with'), fn ($query) => $query->with('translations'))
                            ->get()
                            ->mapWithKeys(function ($item) use ($locale, $mainLocale) {
                                if (method_exists($item, 'translate')) {
                                    $translation = $item->translate($locale);
                                    $isFallback = false;
                                    if (! $translation || ! $translation->title) {
                                        $mainTranslation = $item->translate($mainLocale);
                                        $title = $mainTranslation && $mainTranslation->title ? $mainTranslation->title : 'ID: '.$item->id;
                                        $isFallback = true;
                                    } else {
                                        $title = $translation->title;
                                    }

                                    if ($isFallback) {
                                        $title = $title.' ('.$mainLocale.')';
                                    }

                                    return [$item->id => $title];
                                }

                                return [$item->id => $item->title];
                            })
                            ->toArray();
                    }
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

        return collect($taxonomies)->map(
            fn ($settings, $taxonomy): TagsColumn => TagsColumn::make($taxonomy)
                ->label($settings['label'] ?? ucfirst((string) $taxonomy))
                ->getStateUsing(function ($record) use ($taxonomy, $taxonomyService, $settings) {
                    $relationshipName = $settings['relationship'] ?? $taxonomy;
                    $table = $taxonomyService->getTaxonomyTable($taxonomy);
                    $foreignKey = $taxonomyService->getTaxonomyForeignKey($taxonomy);
                    $relatedKey = $taxonomyService->getTaxonomyRelatedKey($taxonomy);
                    $modelClass = $taxonomyService->getTaxonomyModel($taxonomy);

                    $model = app($modelClass);
                    $modelTable = $model->getTable();

                    $defaultLocalization = Localization::where('is_default', true)->first();
                    $mainLocale = $defaultLocalization?->locale_variant ?? config('app.locale', 'en');
                    $currentLocale = request()->get('lang') ?? $mainLocale;

                    $items = DB::table($table)
                        ->join($modelTable, sprintf('%s.%s', $table, $relatedKey), '=', $modelTable.'.id')
                        ->where(sprintf('%s.%s', $table, $foreignKey), $record->id)
                        ->select($modelTable.'.id')
                        ->pluck('id')
                        ->toArray();

                    if (! method_exists($model, 'translations')) {
                        return DB::table($table)
                            ->join($modelTable, sprintf('%s.%s', $table, $relatedKey), '=', $modelTable.'.id')
                            ->where(sprintf('%s.%s', $table, $foreignKey), $record->id)
                            ->pluck($modelTable.'.title')
                            ->toArray();
                    }

                    // For translatable models, build labels with fallback and (locale) suffix when needed
                    $labels = [];
                    foreach ($items as $itemId) {
                        $entity = app($modelClass)::with('translations')->find($itemId);
                        if (! $entity) {
                            continue;
                        }

                        $translation = $entity->translations->firstWhere('locale', $currentLocale);
                        if ($translation && $translation->title) {
                            $labels[] = $translation->title;

                            continue;
                        }

                        $mainTranslation = $entity->translations->firstWhere('locale', $mainLocale);
                        $title = $mainTranslation && $mainTranslation->title ? $mainTranslation->title : 'ID: '.$entity->id;
                        $labels[] = $title.' ('.$mainLocale.')';
                    }

                    return $labels;
                })
                ->toggleable(isToggledHiddenByDefault: true)
                ->separator(',')
        )->toArray();
    }

    protected static function handleTaxonomies(Model $record, array $data): void
    {
        $taxonomyService = static::getTaxonomyService();
        foreach (array_keys($taxonomyService->getTaxonomies()) as $taxonomy) {
            if (isset($data[$taxonomy])) {
                $relationshipName = $taxonomyService->getTaxonomyRelationship($taxonomy);

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
