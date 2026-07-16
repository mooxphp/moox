<?php

declare(strict_types=1);

namespace Moox\Builder\Database\Eloquent;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Moox\Builder\Concerns\InteractsWithCustomFields;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\TypedValueColumns;

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class CustomFieldsBuilder extends Builder
{
    /**
     * @param  (\Closure(static): mixed)|array<int|string, mixed>|string  $column
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and'): static
    {
        if (is_string($column) && $this->isCustomFieldColumn($column)) {
            if (func_num_args() === 2) {
                $value = $operator;
                $operator = '=';
            }

            return $this->addCustomFieldConstraint($column, $operator, $value, $boolean);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * @param  (\Closure(static): mixed)|array<int|string, mixed>|string  $column
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null): static
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * @param  array<int, mixed>|Arrayable<int, mixed>|\Closure(static): void|string  $values
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false): static
    {
        if (is_string($column) && $this->isCustomFieldColumn($column)) {
            return $this->addCustomFieldInConstraint($column, $values, $boolean, $not);
        }

        return parent::whereIn($column, $values, $boolean, $not);
    }

    /**
     * @param  array<int, mixed>|Arrayable<int, mixed>|\Closure(static): void|string  $values
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'and'): static
    {
        return $this->whereIn($column, $values, $boolean, not: true);
    }

    protected function isCustomFieldColumn(string $column): bool
    {
        $model = $this->getModel();

        if (! in_array(InteractsWithCustomFields::class, class_uses_recursive($model), true)) {
            return false;
        }

        if (str_contains($column, '.')) {
            return false;
        }

        /** @var Model&InteractsWithCustomFields $model */
        return $model->isQueryableCustomField($column);
    }

    /**
     * @return $this
     */
    protected function addCustomFieldConstraint(
        string $field,
        mixed $operator,
        mixed $value,
        string $boolean = 'and',
    ): static {
        if (! in_array($operator, ['=', '!=', '<', '>', '<=', '>=', 'like'], true)) {
            throw new InvalidArgumentException("Unsupported operator [{$operator}] for custom field [{$field}].");
        }

        $model = $this->getModel();

        /** @var class-string<Model&InteractsWithCustomFields> $modelClass */
        $modelClass = $model::class;

        $entity = $modelClass::resolveCustomFieldsEntity();
        $definition = app(CustomFieldsManager::class)
            ->fieldsForEntity($entity)
            ->firstWhere('name', $field);

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown custom field [{$field}] for entity [{$entity}].");
        }

        $valueColumn = TypedValueColumns::columnForType($definition->type);
        $valuesTable = (new FieldValue)->getTable();
        $recordKey = $model->getQualifiedKeyName();
        $locale = app(BuilderLocaleResolver::class)->valuesLocaleForEntity($entity, null, $modelClass);

        return $this->whereExists(function ($subQuery) use ($entity, $field, $valueColumn, $operator, $value, $valuesTable, $recordKey, $locale): void {
            $subQuery->selectRaw('1')
                ->from($valuesTable)
                ->whereColumn("{$valuesTable}.record_id", $recordKey)
                ->where("{$valuesTable}.entity", $entity)
                ->where("{$valuesTable}.field_name", $field)
                ->where("{$valuesTable}.locale", $locale)
                ->where("{$valuesTable}.{$valueColumn}", $operator, $value);
        }, $boolean);
    }

    /**
     * @param  array<int, mixed>|Arrayable<int, mixed>|\Closure(static): void|string  $values
     * @return $this
     */
    protected function addCustomFieldInConstraint(
        string $field,
        mixed $values,
        string $boolean = 'and',
        bool $not = false,
    ): static {
        $model = $this->getModel();

        /** @var class-string<Model&InteractsWithCustomFields> $modelClass */
        $modelClass = $model::class;

        $entity = $modelClass::resolveCustomFieldsEntity();
        $definition = app(CustomFieldsManager::class)
            ->fieldsForEntity($entity)
            ->firstWhere('name', $field);

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown custom field [{$field}] for entity [{$entity}].");
        }

        $valueColumn = TypedValueColumns::columnForType($definition->type);
        $valuesTable = (new FieldValue)->getTable();
        $recordKey = $model->getQualifiedKeyName();
        $locale = app(BuilderLocaleResolver::class)->valuesLocaleForEntity($entity, null, $modelClass);

        return $this->whereExists(function ($subQuery) use ($entity, $field, $valueColumn, $values, $valuesTable, $recordKey, $not, $locale): void {
            $subQuery->selectRaw('1')
                ->from($valuesTable)
                ->whereColumn("{$valuesTable}.record_id", $recordKey)
                ->where("{$valuesTable}.entity", $entity)
                ->where("{$valuesTable}.field_name", $field)
                ->where("{$valuesTable}.locale", $locale);

            if ($not) {
                $subQuery->whereNotIn("{$valuesTable}.{$valueColumn}", $values);
            } else {
                $subQuery->whereIn("{$valuesTable}.{$valueColumn}", $values);
            }
        }, $boolean);
    }
}
