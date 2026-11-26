<?php

declare(strict_types=1);

namespace Moox\Support\Relation\Columns;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Relation\Concerns\InteractsWithConfiguredRelations;

trait HasRelationColumns
{
    use InteractsWithConfiguredRelations;

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
}
