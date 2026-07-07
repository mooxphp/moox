<?php

declare(strict_types=1);

namespace Moox\Builder\Compiler;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Concerns\InteractsWithCustomFields;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\FieldTypes\Capabilities\DisplayFormat;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\BuilderValuesResolver;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\RelationTableColumnQuery;
use Moox\Builder\Support\RelationValueRules;
use Moox\Builder\Support\StorableFieldCollector;
use Moox\Builder\Support\TypedValueColumns;

class TableColumnCompiler
{
    public function __construct(
        protected CustomFieldsManager $customFieldsManager,
        protected StorableFieldCollector $storableFieldCollector,
        protected BuilderLocaleResolver $localeResolver,
        protected BuilderValuesResolver $valuesResolver,
        protected RelationTableColumnQuery $relationTableColumnQuery,
    ) {}

    /**
     * @param  Collection<int, FieldGroupDefinition>  $fieldGroups
     * @param  class-string|null  $resourceClass
     * @return list<Column>
     */
    public function compile(Collection $fieldGroups, ?string $resourceClass = null): array
    {
        if ($fieldGroups->isEmpty()) {
            return [];
        }

        $entity = $resourceClass !== null
            ? $this->customFieldsManager->locationContextForResource($resourceClass)->entity
            : null;

        if ($entity === null) {
            return [];
        }

        /** @var class-string<Model&InteractsWithCustomFields>|null $modelClass */
        $modelClass = $resourceClass !== null ? $resourceClass::getModel() : null;

        if ($modelClass === null || ! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        $locale = $this->localeResolver->valuesLocaleForEntity($entity, null, $modelClass);
        $valuesTable = (new FieldValue)->getTable();

        $fields = $this->storableFieldCollector
            ->definitionsFromList($fieldGroups->flatMap(fn (FieldGroupDefinition $group): Collection => $group->fields))
            ->filter(fn (FieldDefinition $field): bool => $field->showInTable()
                && ($this->isColumnable($field) || $this->isImageColumn($field) || $this->isRelationColumn($field)))
            ->values();

        $columns = [];

        foreach ($fields as $field) {
            $columns[] = $this->compileColumn($field, $entity, $modelClass, $locale, $valuesTable);
        }

        return $columns;
    }

    protected function isColumnable(FieldDefinition $field): bool
    {
        return TypedValueColumns::isColumnableType($field->type);
    }

    protected function isImageColumn(FieldDefinition $field): bool
    {
        return TypedValueColumns::isImageColumnType($field->type);
    }

    protected function isRelationColumn(FieldDefinition $field): bool
    {
        return TypedValueColumns::isRelationColumnType($field->type);
    }

    protected function compileColumn(
        FieldDefinition $field,
        string $entity,
        string $modelClass,
        string $locale,
        string $valuesTable,
    ): Column {
        if ($this->isImageColumn($field)) {
            return $this->compileImageColumn($field);
        }

        if ($this->isRelationColumn($field)) {
            return $this->compileRelationColumn($field, $entity, $locale, $valuesTable);
        }

        $valueColumn = TypedValueColumns::columnForType($field->type);
        $name = $field->name;

        if ($field->type === 'color') {
            $column = ColorColumn::make($name)
                ->label($field->label)
                ->getStateUsing(fn (Model $record): ?string => $this->resolvePresentedValue($field, $record));
        } elseif ($field->type === 'toggle') {
            $column = IconColumn::make($name)
                ->label($field->label)
                ->boolean()
                ->getStateUsing(fn (Model $record): bool => (bool) $this->resolvePresentedValue($field, $record));
        } else {
            $column = TextColumn::make($name)
                ->label($field->label)
                ->getStateUsing(fn (Model $record): mixed => $this->resolvePresentedValue($field, $record));

            if (in_array($field->type, ['textarea', 'rich_text'], true)) {
                $column->limit(50);
            }

            if ($field->columnBadge()) {
                $column->badge();
            }

            if (($color = $field->columnColor()) !== null) {
                $column->color($color);
            }

            if (($icon = $field->columnIcon()) !== null) {
                $column->icon($icon);
            }

            $this->applyTextColumnPresentation($column, $field);
        }

        $column->toggleable(isToggledHiddenByDefault: $field->isColumnHiddenByDefault());

        if ($field->isColumnSortable()) {
            $column->sortable(query: function (Builder $query, string $direction) use (
                $entity,
                $field,
                $locale,
                $valuesTable,
                $valueColumn,
            ): Builder {
                $recordKey = $query->getModel()->getQualifiedKeyName();

                return $query->orderBy(
                    FieldValue::query()
                        ->select($valueColumn)
                        ->from($valuesTable)
                        ->whereColumn("{$valuesTable}.record_id", $recordKey)
                        ->where("{$valuesTable}.entity", $entity)
                        ->where("{$valuesTable}.field_name", $field->name)
                        ->where("{$valuesTable}.locale", $locale)
                        ->limit(1),
                    $direction,
                );
            });
        }

        if ($field->isColumnSearchable()) {
            $column->searchable(query: function (Builder $query, string $search) use ($name): Builder {
                return $query->where($name, 'like', "%{$search}%");
            });
        }

        return $column;
    }

    protected function applyTextColumnPresentation(TextColumn $column, FieldDefinition $field): void
    {
        $column->placeholder('—');

        match ($field->type) {
            'date' => $column->date(DisplayFormat::resolveForField($field)),
            'datetime' => $column->dateTime(DisplayFormat::resolveForField($field)),
            'time' => $column->time(DisplayFormat::resolveForField($field)),
            'number', 'range' => $column->numeric(decimalPlaces: $this->numericDecimalPlaces($field)),
            default => null,
        };
    }

    protected function numericDecimalPlaces(FieldDefinition $field): ?int
    {
        $step = $field->config['step'] ?? null;

        if (! is_numeric($step)) {
            return null;
        }

        $stepString = (string) $step;

        if (! str_contains($stepString, '.')) {
            return 0;
        }

        return strlen(rtrim(substr($stepString, strpos($stepString, '.') + 1), '0'));
    }

    protected function compileImageColumn(FieldDefinition $field): Column
    {
        $column = ImageColumn::make($field->name)
            ->label($field->label)
            ->checkFileExistence(false)
            ->getStateUsing(fn (Model $record): array|string|null => $this->resolveImageState($field, $record))
            ->toggleable(isToggledHiddenByDefault: $field->isColumnHiddenByDefault());

        $size = match ($field->columnImageSize()) {
            'sm' => 32,
            'lg' => 56,
            default => 40,
        };

        match ($field->columnImageShape()) {
            'circular' => $column->circular()->imageSize($size),
            'square' => $column->square()->imageSize($size),
            default => $column->imageHeight($size),
        };

        if ($field->type === 'gallery') {
            $column->stacked()
                ->limit(3)
                ->limitedRemainingText();
        }

        return $column;
    }

    protected function compileRelationColumn(
        FieldDefinition $field,
        string $entity,
        string $locale,
        string $valuesTable,
    ): Column {
        $column = TextColumn::make($field->name)
            ->label($field->label)
            ->placeholder('—')
            ->getStateUsing(function (Model $record) use ($field): ?string {
                $presented = $this->resolvePresentedValue($field, $record);

                return $this->formatRelationColumnState($presented);
            })
            ->toggleable(isToggledHiddenByDefault: $field->isColumnHiddenByDefault());

        if ($field->columnBadge() && ! RelationValueRules::isMultiple($field)) {
            $column->badge();
        }

        if ($this->relationTableColumnQuery->canQuery($field)) {
            if ($field->isColumnSortable()) {
                $column->sortable(query: function (Builder $query, string $direction) use (
                    $field,
                    $entity,
                    $locale,
                    $valuesTable,
                ): Builder {
                    return $this->relationTableColumnQuery->applySort(
                        $query,
                        $field,
                        $entity,
                        $locale,
                        $valuesTable,
                        $direction,
                    );
                });
            }

            if ($field->isColumnSearchable()) {
                $column->searchable(query: function (Builder $query, string $search) use (
                    $field,
                    $entity,
                    $locale,
                    $valuesTable,
                ): Builder {
                    return $this->relationTableColumnQuery->applySearch(
                        $query,
                        $field,
                        $entity,
                        $locale,
                        $valuesTable,
                        $search,
                    );
                });
            }
        }

        return $column;
    }

    protected function formatRelationColumnState(mixed $presented): ?string
    {
        if ($presented === null) {
            return null;
        }

        if (is_array($presented) && array_key_exists('label', $presented)) {
            return (string) $presented['label'];
        }

        if (! is_array($presented) || ! array_is_list($presented)) {
            return null;
        }

        $labels = collect($presented)
            ->map(fn (mixed $item): ?string => is_array($item) ? ($item['label'] ?? null) : null)
            ->filter()
            ->values()
            ->all();

        return $labels === [] ? null : implode(', ', $labels);
    }

    /**
     * @return list<string>|string|null
     */
    protected function resolveImageState(FieldDefinition $field, Model $record): array|string|null
    {
        $value = $this->resolvePresentedValue($field, $record);

        if ($value === null) {
            return null;
        }

        if ($field->type === 'gallery') {
            return collect(is_array($value) ? $value : [])
                ->map(fn (mixed $item): ?string => $this->imageUrlFromPresented($item))
                ->filter()
                ->values()
                ->all();
        }

        return $this->imageUrlFromPresented($value);
    }

    protected function imageUrlFromPresented(mixed $item): ?string
    {
        if (! is_array($item)) {
            return null;
        }

        $url = $item['thumbnail_url'] ?? $item['preview_url'] ?? $item['url'] ?? null;

        return filled($url) ? (string) $url : null;
    }

    protected function resolvePresentedValue(FieldDefinition $field, Model $record): mixed
    {
        if (! method_exists($record, 'customField')) {
            return null;
        }

        /** @var Model&InteractsWithCustomFields $record */
        $value = $record->customField($field->name);

        if ($value === null) {
            return null;
        }

        return $this->valuesResolver->presentFieldValue($field, $value);
    }
}
