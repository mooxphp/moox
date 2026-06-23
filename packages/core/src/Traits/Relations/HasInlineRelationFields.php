<?php

declare(strict_types=1);

namespace Moox\Core\Traits\Relations;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Core\Forms\TaxonomyCreateForm;
use Moox\Core\Services\RelationService;
use Moox\Localization\Models\Localization;

trait HasInlineRelationFields
{
    protected static function getResourceName(): string
    {
        $modelClass = static::getModel();

        if (! method_exists($modelClass, 'getResourceName')) {
            throw new \LogicException(sprintf('Model %s must implement static getResourceName().', $modelClass));
        }

        return $modelClass::getResourceName();
    }

    protected static function relationServiceFor(string $resource): RelationService
    {
        return app(RelationService::class)->forResource($resource);
    }

    /**
     * @return array<int, Select|SelectTree>
     */
    public static function getInlineRelationFields(): array
    {
        $service = static::relationServiceFor(static::getResourceName());

        return collect($service->inlineRelationConfigs())
            ->map(fn (array $settings, string $key): Select|SelectTree => static::createInlineRelationField($key, $settings, $service))
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected static function createInlineRelationField(string $key, array $settings, RelationService $service): Select|SelectTree
    {
        $modelClass = $service->relatedModel($key);
        $service->validate($key);
        $isHierarchical = $settings['hierarchical'] ?? false;
        $createFormClass = $settings['createForm'] ?? TaxonomyCreateForm::class;

        $commonConfig = [
            'label' => $settings['label'] ?? ucfirst($key),
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

                    $newTaxonomy = $modelClass::create($nonTranslatableData);
                    $newTaxonomy->createTranslation($locale, $translationData);

                    $newTaxonomy->refresh();
                } else {
                    $newTaxonomy = $modelClass::create($data);
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
            $selectTree = SelectTree::make($key)
                ->relationship(
                    relationship: $key,
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

        $select = Select::make($key)
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

    /**
     * @return array<int, SelectFilter>
     */
    public static function getInlineRelationFilters(): array
    {
        $service = static::relationServiceFor(static::getResourceName());
        $relations = $service->inlineRelationConfigs();
        $resourceModel = static::getModel();
        $resourceTable = app($resourceModel)->getTable();

        return collect($relations)->map(function (array $settings, string $key) use ($service, $resourceTable): SelectFilter {
            $relationModel = $service->relatedModel($key);
            $pivotTable = $service->pivotTable($key);
            $foreignKey = $service->foreignKey($key);
            $relatedKey = $service->relatedKey($key);
            $taxonomyTable = app($relationModel)->getTable();

            return SelectFilter::make($key)
                ->label($settings['label'] ?? ucfirst($key))
                ->multiple()
                ->options(
                    function () use ($relationModel, $pivotTable, $foreignKey, $relatedKey, $resourceTable) {
                        $defaultLocalization = Localization::where('is_default', true)->first();
                        $mainLocale = $defaultLocalization?->locale_variant ?? config('app.locale', 'en');
                        $locale = request()->query('lang') ?? $mainLocale;

                        $usedIds = DB::table($pivotTable)
                            ->join($resourceTable, $pivotTable.'.'.$foreignKey, '=', $resourceTable.'.id')
                            ->distinct()
                            ->pluck($pivotTable.'.'.$relatedKey)
                            ->filter()
                            ->toArray();

                        if ($usedIds === []) {
                            return [];
                        }

                        return app($relationModel)::query()
                            ->whereIn('id', $usedIds)
                            ->when(method_exists($relationModel, 'with'), fn ($query) => $query->with('translations'))
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
                    if ($selectedIds !== []) {
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

    /**
     * @return array<int, TagsColumn>
     */
    protected static function getInlineRelationColumns(): array
    {
        $service = static::relationServiceFor(static::getResourceName());
        $relations = $service->inlineRelationConfigs();

        return collect($relations)->map(
            fn (array $settings, string $key): TagsColumn => TagsColumn::make($key)
                ->label($settings['label'] ?? ucfirst($key))
                ->getStateUsing(function ($record) use ($key, $service, $settings) {
                    $relationshipName = $settings['relationship'] ?? $key;
                    $table = $service->pivotTable($key);
                    $foreignKey = $service->foreignKey($key);
                    $relatedKey = $service->relatedKey($key);
                    $modelClass = $service->relatedModel($key);

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

    /**
     * @param  array<string, mixed>  $data
     */
    protected static function handleInlineRelations(Model $record, array $data): void
    {
        $service = static::relationServiceFor(static::getResourceName());

        foreach (array_keys($service->inlineRelationConfigs()) as $key) {
            if (isset($data[$key])) {
                $relationshipName = $service->relationshipMethod($key);

                if (method_exists($record, $relationshipName)) {
                    $record->{$relationshipName}()->sync($data[$key]);
                } elseif (method_exists($record, 'syncRelation')) {
                    $record->syncRelation($key, $data[$key]);
                }
            }
        }
    }

    protected static function addInlineRelationsToQuery(Builder $query): Builder
    {
        $service = static::relationServiceFor(static::getResourceName());

        foreach (array_keys($service->inlineRelationConfigs()) as $key) {
            $relationshipName = $service->relationshipMethod($key);

            if (method_exists($query->getModel(), $relationshipName)) {
                $query->with($relationshipName);
            }
        }

        return $query;
    }
}
