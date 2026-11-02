<?php

declare(strict_types=1);

namespace Moox\Support\Relation;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Filament\FeatureFields;

trait HasRelationForm
{
    protected static function relationSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'relation'))
            ->schema([
                ...static::configuredRelationFields(),
                ...static::additionalRelationFields(),
            ])
            ->columns(2);
    }

    /**
     * @return list<Select|TextInput>
     */
    protected static function additionalRelationFields(): array
    {
        return [];
    }

    /**
     * @return list<Select|TextInput>
     */
    protected static function configuredRelationFields(): array
    {
        $relations = config(static::featureResourceName().'.relations', []);
        $fields = [];

        if (! is_array($relations)) {
            return [];
        }

        foreach ($relations as $relation => $configured) {
            if (! static::isListedBelongsTo($relation, $configured) || static::isMorphRelation($configured)) {
                continue;
            }

            $column = $configured['foreign_key'] ?? null;

            if (! is_string($column) || $column === '') {
                continue;
            }

            $fields[] = static::relationField($column, $relation, $configured);
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function relationField(string $column, string $relation, array $configured): Select|TextInput
    {
        $translation = static::featureTranslation();
        $model = $configured['model'] ?? null;
        $titleAttribute = $configured['title_attribute'] ?? null;

        if (! is_string($model) || ! is_string($titleAttribute) || ! is_a($model, Model::class, true)) {
            return TextInput::make($column)
                ->label(FeatureFields::label($translation, $column))
                ->numeric()
                ->rules(static::featureRules($column));
        }

        $isTranslated = is_a($model, 'Astrotomic\\Translatable\\Contracts\\Translatable', true);

        $field = Select::make($column)
            ->label(FeatureFields::label($translation, $column))
            ->relationship(
                $relation,
                $titleAttribute,
                $isTranslated
                    ? function (Builder $query, ?string $search) use ($titleAttribute): Builder {
                        if (filled($search)) {
                            $query->whereTranslationLike($titleAttribute, '%'.$search.'%');
                        }

                        return $query->orderByTranslation($titleAttribute);
                    }
                : null,
            )
            ->searchable($isTranslated ? [] : true)
            ->preload()
            ->rules(static::featureRules($column));

        if ($isTranslated) {
            $field->getOptionLabelFromRecordUsing(
                fn (Model $record): string => (string) ($record->getAttribute($titleAttribute) ?? $record->getKey())
            );
        }

        $relatedResource = $configured['related_resource'] ?? null;

        if (is_string($relatedResource) && is_a($relatedResource, Resource::class, true)) {
            $field->suffixAction(
                Action::make('open_'.$relation)
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->tooltip(FeatureFields::label($translation, $column))
                    ->url(fn (mixed $state): ?string => filled($state)
                        ? $relatedResource::getUrl('view', ['record' => $state])
                        : null)
                    ->visible(fn (mixed $state): bool => filled($state)),
                true,
            );
        }

        return $field;
    }

    /**
     * @return list<TextColumn>
     */
    protected static function configuredRelationColumns(): array
    {
        $columns = [];

        foreach (static::belongsToRelations() as $relation => $configured) {
            if (($configured['index_column'] ?? false) !== true || static::isMorphRelation($configured)) {
                continue;
            }

            $column = static::relationIndexColumn($relation, $configured);

            if ($column instanceof TextColumn) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * @return list<SelectFilter>
     */
    protected static function configuredRelationFilters(): array
    {
        $filters = [];

        foreach (static::belongsToRelations() as $relation => $configured) {
            if (($configured['filterable'] ?? false) !== true) {
                continue;
            }

            $filter = static::isMorphRelation($configured)
                ? static::morphOwnerFilter($configured)
                : static::relationSelectFilter($relation, $configured);

            if ($filter instanceof SelectFilter) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function relationIndexColumn(string $relation, array $configured): ?TextColumn
    {
        $titleAttribute = $configured['title_attribute'] ?? null;
        $column = $configured['foreign_key'] ?? null;

        if (! is_string($titleAttribute) || $titleAttribute === '' || ! is_string($column) || $column === '') {
            return null;
        }

        $textColumn = TextColumn::make($relation.'.'.$titleAttribute)
            ->label(FeatureFields::label(static::featureTranslation(), $column));

        if (! static::relationTitleIsTranslated($configured)) {
            $textColumn->searchable();
        }

        return $textColumn;
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function relationSelectFilter(string $relation, array $configured): ?SelectFilter
    {
        $column = $configured['foreign_key'] ?? null;
        $titleAttribute = $configured['title_attribute'] ?? null;
        $model = $configured['model'] ?? null;

        if (! is_string($column) || $column === '' || ! is_string($titleAttribute) || $titleAttribute === '') {
            return null;
        }

        $label = FeatureFields::label(static::featureTranslation(), $column);

        if (static::relationTitleIsTranslated($configured) && is_string($model) && is_a($model, Model::class, true)) {
            return SelectFilter::make($column)
                ->label($label)
                ->options(fn (): array => $model::query()
                    ->get()
                    ->mapWithKeys(fn (Model $record): array => [
                        $record->getKey() => (string) ($record->getAttribute($titleAttribute) ?: $record->getKey()),
                    ])
                    ->all())
                ->searchable();
        }

        return SelectFilter::make($column)
            ->label($label)
            ->relationship($relation, $titleAttribute)
            ->searchable();
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function morphOwnerFilter(array $configured): ?SelectFilter
    {
        $morphName = $configured['morph_name'] ?? null;

        if (! is_string($morphName) || $morphName === '') {
            return null;
        }

        $label = FeatureFields::label(static::featureTranslation(), $morphName);

        return SelectFilter::make($morphName)
            ->label($label)
            ->searchable()
            ->getSearchResultsUsing(fn (?string $search): array => static::morphOwnerOptions($search ?? ''))
            ->getOptionLabelUsing(fn (?string $value): ?string => static::morphOwnerLabel($value))
            ->indicateUsing(function (SelectFilter $filter, array $state): array {
                $value = $state['value'] ?? null;

                if (! is_string($value) || $value === '') {
                    return [];
                }

                $ownerLabel = static::morphOwnerLabel($value);

                if ($ownerLabel === null || $ownerLabel === '') {
                    return [];
                }

                return [Indicator::make($filter->getLabel().': '.$ownerLabel)];
            })
            ->query(function (Builder $query, array $data) use ($morphName): Builder {
                $owner = static::morphOwnerFromKey($data['value'] ?? null);

                if ($owner === null) {
                    return $query;
                }

                return $query
                    ->where($morphName.'_type', $owner['type'])
                    ->where($morphName.'_id', $owner['id']);
            });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function belongsToRelations(): array
    {
        $relations = config(static::featureResourceName().'.relations', []);

        if (! is_array($relations)) {
            return [];
        }

        $belongsTo = [];

        foreach ($relations as $relation => $configured) {
            if (! static::isListedBelongsTo($relation, $configured)) {
                continue;
            }

            $belongsTo[$relation] = $configured;
        }

        return $belongsTo;
    }

    protected static function isListedBelongsTo(mixed $relation, mixed $configured): bool
    {
        return is_string($relation)
            && is_array($configured)
            && ($configured['kind'] ?? null) === 'belongs_to';
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function isMorphRelation(array $configured): bool
    {
        return is_string($configured['morph_name'] ?? null) && $configured['morph_name'] !== '';
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function relationTitleIsTranslated(array $configured): bool
    {
        $model = $configured['model'] ?? null;

        return is_string($model) && is_a($model, 'Astrotomic\\Translatable\\Contracts\\Translatable', true);
    }

    /**
     * @return array<string, string>
     */
    private static function morphOwnerOptions(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $like = '%'.addcslashes($search, '%_\\').'%';
        $options = [];

        foreach (config(static::featureResourceName().'.owner_types', []) as $class => $definition) {
            if (! is_string($class) || ! is_array($definition) || ! is_a($class, Model::class, true)) {
                continue;
            }

            $columns = static::ownerTitleColumns($definition);

            if ($columns === []) {
                continue;
            }

            foreach ($class::query()->where(function (Builder $query) use ($columns, $like): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', $like);
                }
            })->limit(15)->get() as $owner) {
                $options[$class.'|'.$owner->getKey()] = static::ownerRecordLabel($owner);
            }
        }

        return $options;
    }

    private static function morphOwnerLabel(?string $key): ?string
    {
        $owner = static::morphOwnerFromKey($key);

        if ($owner === null) {
            return null;
        }

        $record = $owner['type']::query()->find($owner['id']);

        return $record instanceof Model ? static::ownerRecordLabel($record) : null;
    }

    /**
     * @return array{type: class-string<Model>, id: string}|null
     */
    private static function morphOwnerFromKey(mixed $key): ?array
    {
        if (! is_string($key) || ! str_contains($key, '|')) {
            return null;
        }

        [$type, $id] = explode('|', $key, 2);
        $ownerTypes = config(static::featureResourceName().'.owner_types', []);

        if ($id === '' || ! is_array($ownerTypes) || ! isset($ownerTypes[$type]) || ! is_a($type, Model::class, true)) {
            return null;
        }

        return [
            'type' => $type,
            'id' => $id,
        ];
    }

    private static function ownerRecordLabel(Model $owner): string
    {
        if (method_exists(static::class, 'labelForOwner')) {
            $label = static::labelForOwner($owner);

            if (is_string($label) && $label !== '') {
                return $label;
            }
        }

        return (string) $owner->getKey();
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<string>
     */
    private static function ownerTitleColumns(array $definition): array
    {
        $columns = $definition['title_attributes'] ?? null;

        if (! is_array($columns)) {
            $titleAttribute = $definition['title_attribute'] ?? null;
            $columns = is_string($titleAttribute) && $titleAttribute !== '' ? [$titleAttribute] : [];
        }

        return array_values(array_filter(
            $columns,
            fn (mixed $column): bool => is_string($column) && $column !== '',
        ));
    }

    protected static function featureResourceName(): string
    {
        $model = static::getModel();

        if (! method_exists($model, 'getResourceName')) {
            throw new \RuntimeException(sprintf('Model %s must implement getResourceName().', $model));
        }

        return $model::getResourceName();
    }
}
