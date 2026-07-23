<?php

declare(strict_types=1);

namespace Moox\Builder\Compiler;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Concerns\InteractsWithCustomFields;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\CustomFieldTableFilterQuery;
use Moox\Builder\Support\FilterableFieldTypes;
use Moox\Builder\Support\RelationTargetResolver;
use Moox\Builder\Support\StorableFieldCollector;

class TableFilterCompiler
{
    public function __construct(
        protected CustomFieldsManager $customFieldsManager,
        protected StorableFieldCollector $storableFieldCollector,
        protected CustomFieldTableFilterQuery $filterQuery,
        protected RelationTargetResolver $relationTargetResolver,
    ) {
    }

    /**
     * @param  Collection<int, FieldGroupDefinition>  $fieldGroups
     * @param  class-string|null  $resourceClass
     * @return list<BaseFilter>
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

        $filterableFields = $this->storableFieldCollector
            ->definitionsFromList($fieldGroups->flatMap(fn (FieldGroupDefinition $group): Collection => $group->fields))
            ->filter(fn (FieldDefinition $field): bool => $field->showInFilter())
            ->values();

        $filters = [];

        foreach ($filterableFields->filter(fn (FieldDefinition $field): bool => FilterableFieldTypes::supports($field)) as $field) {
            $filter = $this->compileFilter($field, $entity, $modelClass);

            if ($filter !== null) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * @param  class-string<Model&InteractsWithCustomFields>  $modelClass
     */
    protected function compileFilter(
        FieldDefinition $field,
        string $entity,
        string $modelClass,
    ): ?BaseFilter {
        return match ($field->type) {
            'toggle' => $this->compileToggleFilter($field, $entity, $modelClass),
            'select', 'radio', 'button_group' => $this->compileChoiceFilter($field, $entity, $modelClass),
            'relation' => $this->compileRelationFilter($field, $entity, $modelClass),
            'text', 'textarea', 'email', 'url', 'rich_text' => $this->compileTextFilter($field),
            'number', 'range', 'date', 'datetime' => $this->compileRangeFilter($field),
            default => null,
        };
    }

    /**
     * A simple "contains" search filter chip for text-like fields.
     */
    protected function compileTextFilter(FieldDefinition $field): Filter
    {
        return Filter::make($field->name)
            ->label($field->label)
            ->schema([
                TextInput::make('value')
                    ->label($field->label),
            ])
            ->query(fn (Builder $query, array $data): Builder => $this->filterQuery->applyContains($query, $field, $data['value'] ?? null))
            ->indicateUsing(fn (array $data): ?string => filled($data['value'] ?? null)
                ? "{$field->label}: {$data['value']}"
                : null);
    }

    /**
     * An inclusive "from"/"until" range filter chip for number, range,
     * date, and datetime fields, since an exact-match chip rarely makes
     * sense for these types.
     */
    protected function compileRangeFilter(FieldDefinition $field): Filter
    {
        [$fromInput, $untilInput] = match ($field->type) {
            'date' => [DatePicker::make('from')->native(false), DatePicker::make('until')->native(false)],
            'datetime' => [DateTimePicker::make('from')->native(false), DateTimePicker::make('until')->native(false)],
            default => [TextInput::make('from')->numeric(), TextInput::make('until')->numeric()],
        };

        return Filter::make($field->name)
            ->label($field->label)
            ->schema([
                $fromInput->label(__('builder::builder.filters.from')),
                $untilInput->label(__('builder::builder.filters.until')),
            ])
            ->query(fn (Builder $query, array $data): Builder => $this->filterQuery->applyRange(
                $query,
                $field,
                $data['from'] ?? null,
                $data['until'] ?? null,
            ))
            ->indicateUsing(function (array $data) use ($field): ?string {
                $from = $data['from'] ?? null;
                $until = $data['until'] ?? null;

                return match (true) {
                    filled($from) && filled($until) => "{$field->label}: {$from} – {$until}",
                    filled($from) => "{$field->label}: ≥ {$from}",
                    filled($until) => "{$field->label}: ≤ {$until}",
                    default => null,
                };
            });
    }

    /**
     * @param  class-string<Model&InteractsWithCustomFields>  $modelClass
     */
    protected function compileToggleFilter(
        FieldDefinition $field,
        string $entity,
        string $modelClass,
    ): TernaryFilter {
        return TernaryFilter::make($field->name)
            ->label($field->label)
            ->queries(
                true: fn (Builder $query): Builder => $this->filterQuery->applyEquals($query, $field, $entity, $modelClass, true),
                false: fn (Builder $query): Builder => $this->filterQuery->applyEquals($query, $field, $entity, $modelClass, false),
            );
    }

    /**
     * @param  class-string<Model&InteractsWithCustomFields>  $modelClass
     */
    protected function compileChoiceFilter(
        FieldDefinition $field,
        string $entity,
        string $modelClass,
    ): SelectFilter {
        $options = collect($field->options)
            ->mapWithKeys(fn (array $option): array => [
                (string) $option['value'] => (string) $option['label'],
            ])
            ->all();

        return SelectFilter::make($field->name)
            ->label($field->label)
            ->options($options)
            ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                ? $this->filterQuery->applyEquals($query, $field, $entity, $modelClass, $data['value'])
                : $query);
    }

    /**
     * @param  class-string<Model&InteractsWithCustomFields>  $modelClass
     */
    protected function compileRelationFilter(
        FieldDefinition $field,
        string $entity,
        string $modelClass,
    ): SelectFilter {
        $relatedEntity = (string) $field->config['related_entity'];

        return SelectFilter::make($field->name)
            ->label($field->label)
            ->native(false)
            ->searchable()
            ->preload()
            ->options(fn (): array => $this->relationTargetResolver->search($relatedEntity, ''))
            ->getSearchResultsUsing(fn (string $search): array => $this->relationTargetResolver->search($relatedEntity, $search))
            ->getOptionLabelUsing(function (mixed $value) use ($relatedEntity): string {
                if (! filled($value)) {
                    return '';
                }

                $labels = $this->relationTargetResolver->labelsFor($relatedEntity, [$value]);

                return $labels[$value]
                    ?? $labels[(int) $value]
                    ?? $labels[(string) $value]
                    ?? (string) $value;
            })
            ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                ? $this->filterQuery->applyEquals($query, $field, $entity, $modelClass, $data['value'])
                : $query);
    }
}
