<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Concerns\InteractsWithCustomFields;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldValue;

/**
 * Applies table-filter constraints for custom fields on entity list queries.
 */
final class CustomFieldTableFilterQuery
{
    public function __construct(
        protected BuilderLocaleResolver $localeResolver,
    ) {
    }

    /**
     * @param  class-string<Model&InteractsWithCustomFields>  $modelClass
     */
    public function applyEquals(
        Builder $query,
        FieldDefinition $field,
        string $entity,
        string $modelClass,
        mixed $value,
    ): Builder {
        if ($field->type === 'relation') {
            return $this->applyRelationEquals($query, $field, $entity, $modelClass, $value);
        }

        return $query->where($field->name, $this->normalizeValue($field, $value));
    }

    /**
     * Case-insensitive "contains" search, for the free-text filter chip on
     * text-like custom fields.
     */
    public function applyContains(Builder $query, FieldDefinition $field, mixed $value): Builder
    {
        if (! filled($value)) {
            return $query;
        }

        return $query->where($field->name, 'like', '%'.$value.'%');
    }

    /**
     * Inclusive "from"/"until" bounds, for the range filter chip on
     * number, range, date, and datetime custom fields. Both bounds are
     * optional and combine with AND when both are given.
     */
    public function applyRange(Builder $query, FieldDefinition $field, mixed $from, mixed $until): Builder
    {
        if (filled($from)) {
            $query->where($field->name, '>=', $from);
        }

        if (filled($until)) {
            $query->where($field->name, '<=', $until);
        }

        return $query;
    }

    /**
     * @param  class-string<Model&InteractsWithCustomFields>  $modelClass
     */
    protected function applyRelationEquals(
        Builder $query,
        FieldDefinition $field,
        string $entity,
        string $modelClass,
        mixed $value,
    ): Builder {
        if (! filled($value)) {
            return $query;
        }

        $valuesTable = (new FieldValue)->getTable();
        $recordKey = $query->getModel()->getQualifiedKeyName();
        $locale = $this->localeResolver->valuesLocaleForEntity($entity, null, $modelClass);
        $relatedId = is_numeric($value) ? (int) $value : $value;

        return $query->whereExists(function ($subQuery) use (
            $entity,
            $field,
            $valuesTable,
            $recordKey,
            $locale,
            $relatedId,
        ): void {
            $subQuery->selectRaw('1')
                ->from($valuesTable)
                ->whereColumn("{$valuesTable}.record_id", $recordKey)
                ->where("{$valuesTable}.entity", $entity)
                ->where("{$valuesTable}.field_name", $field->name)
                ->where("{$valuesTable}.locale", $locale)
                ->where(function ($builder) use ($relatedId): void {
                    $builder->where('value_json', $relatedId)
                        ->orWhere('value_json', json_encode($relatedId));
                });
        });
    }

    protected function normalizeValue(FieldDefinition $field, mixed $value): mixed
    {
        if ($field->type === 'toggle') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }
}
