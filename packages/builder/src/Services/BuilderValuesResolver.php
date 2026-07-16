<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Concerns\InteractsWithCustomFields;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\ConditionalLogic;
use Moox\Builder\Support\TypedValueColumns;

class BuilderValuesResolver
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected DefaultValue $defaultValue,
        protected BuilderLocaleResolver $localeResolver,
        protected ClonedFieldGroupResolver $clonedFieldGroupResolver,
    ) {}

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, mixed>
     */
    public function resolveFromRows(Collection $fields, Collection $rowsByFieldName, bool $mergeDefaults = true): array
    {
        $values = [];

        foreach ($fields as $field) {
            $fieldType = $this->fieldTypeRegistry->get($field->type);

            if (! $fieldType->storesValue()) {
                continue;
            }

            $row = $rowsByFieldName->get($field->name);

            if ($row !== null) {
                $raw = TypedValueColumns::read($row, $field->type);
                $values[$field->name] = $fieldType->castValue($raw, $field);

                continue;
            }

            if ($mergeDefaults) {
                $default = $this->resolveDefault($field);

                if ($default !== null || ($field->type === 'toggle' && $this->defaultValue->hasConfiguredDefault($field))) {
                    $values[$field->name] = $default;
                }
            }
        }

        return $values;
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function mergeDefaults(Collection $fields, array $values): array
    {
        foreach ($fields as $field) {
            if (array_key_exists($field->name, $values)
                && ! $this->defaultValue->shouldApplyDefault($values[$field->name], $field->type)) {
                continue;
            }

            $default = $this->resolveDefault($field);

            if ($default !== null || ($field->type === 'toggle' && $this->defaultValue->hasConfiguredDefault($field))) {
                $values[$field->name] = $default;
            }
        }

        return $values;
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function present(Collection $fields, array $values): array
    {
        $presented = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field->name, $values)) {
                continue;
            }

            $presented[$field->name] = $this->presentFieldValue($field, $values[$field->name]);
        }

        return $presented;
    }

    public function presentFieldValue(FieldDefinition $field, mixed $value): mixed
    {
        $fieldType = $this->fieldTypeRegistry->get($field->type);

        if ($fieldType->hasSubFields() && is_array($value)) {
            return match ($field->type) {
                'repeater' => $this->presentRepeaterRows($field, $value),
                'flexible_content' => $this->presentFlexibleContentItems($field, $value),
                'clone' => $this->presentCompoundRow($this->clonedFieldGroupResolver->compoundChildren($field), $value),
                default => $this->presentCompoundRow($field->children, $value),
            };
        }

        return $fieldType->presentValue($value, $field);
    }

    public function persistFieldValue(FieldDefinition $field, mixed $value): mixed
    {
        $fieldType = $this->fieldTypeRegistry->get($field->type);

        if ($fieldType->hasSubFields() && is_array($value)) {
            $value = $fieldType->castValue($value, $field);

            return match ($field->type) {
                'repeater' => $this->persistRepeaterRows($field, $value),
                'flexible_content' => $this->persistFlexibleContentItems($field, $value),
                'clone' => $this->persistCompoundRow($this->clonedFieldGroupResolver->compoundChildren($field), $value),
                default => $this->persistCompoundRow($field->children, $value),
            };
        }

        return $fieldType->persistValue($value, $field);
    }

    /**
     * @param  Collection<int, FieldDefinition>  $children
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function persistCompoundRow(Collection $children, array $row): array
    {
        $persisted = [];

        foreach ($children as $child) {
            $value = array_key_exists($child->name, $row)
                ? $row[$child->name]
                : null;

            if (! ConditionalLogic::isVisibleForValues($child, $row)) {
                $value = null;
            }

            $persisted[$child->name] = $this->persistFieldValue($child, $value);
        }

        return $persisted;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function persistRepeaterRows(FieldDefinition $field, array $rows): array
    {
        return array_values(array_map(
            fn (array $row): array => $this->persistCompoundRow($field->children, $row),
            $rows,
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function persistFlexibleContentItems(FieldDefinition $field, array $items): array
    {
        $layouts = $field->layouts()->keyBy('name');

        return array_values(array_map(function (array $item) use ($layouts): array {
            $type = (string) ($item['type'] ?? '');
            $data = is_array($item['data'] ?? null) ? $item['data'] : [];
            $layout = $layouts->get($type);

            return [
                'type' => $type,
                'data' => $layout !== null
                    ? $this->persistCompoundRow($layout->children, $data)
                    : $data,
            ];
        }, $items));
    }

    /**
     * @param  Collection<int, FieldDefinition>  $children
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function presentCompoundRow(Collection $children, array $row): array
    {
        $presented = [];

        foreach ($children as $child) {
            if (! array_key_exists($child->name, $row)) {
                continue;
            }

            $presented[$child->name] = $this->presentFieldValue($child, $row[$child->name]);
        }

        return $presented;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function presentRepeaterRows(FieldDefinition $field, array $rows): array
    {
        return array_values(array_map(
            fn (array $row): array => $this->presentCompoundRow($field->children, $row),
            $rows,
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function presentFlexibleContentItems(FieldDefinition $field, array $items): array
    {
        $layouts = $field->layouts()->keyBy('name');

        return array_values(array_map(function (array $item) use ($layouts): array {
            $type = (string) ($item['type'] ?? '');
            $data = is_array($item['data'] ?? null) ? $item['data'] : [];
            $layout = $layouts->get($type);

            return [
                'type' => $type,
                'data' => $layout !== null
                    ? $this->presentCompoundRow($layout->children, $data)
                    : $data,
            ];
        }, $items));
    }

    /**
     * @param  iterable<int, Model>  $models
     */
    public function eagerLoad(iterable $models, string $entity, Collection $fields, ?string $locale = null): void
    {
        $collection = $models instanceof EloquentCollection
            ? $models
            : new EloquentCollection(is_array($models) ? $models : iterator_to_array($models));

        if ($collection->isEmpty() || $fields->isEmpty()) {
            return;
        }

        $model = $collection->first();

        if (! $model instanceof Model || ! method_exists($model, 'setCustomFieldsCache')) {
            return;
        }

        $keyName = $model->getKeyName();
        $localeChain = $this->localeResolver->valuesFallbackChainForEntity($entity, $locale, $model::class);

        $rows = FieldValue::query()
            ->where('entity', $entity)
            ->whereIn('record_id', $collection->pluck($keyName))
            ->whereIn('field_name', $fields->pluck('name'))
            ->whereIn('locale', $localeChain)
            ->get()
            ->groupBy('record_id');

        foreach ($collection as $record) {
            /** @var Model&InteractsWithCustomFields $record */
            $recordRows = $rows->get($record->getKey(), collect())->groupBy('field_name');
            $resolvedRows = collect();

            foreach ($fields as $field) {
                $fieldRows = $recordRows->get($field->name, collect());
                $row = null;

                foreach ($localeChain as $candidate) {
                    $row = $fieldRows->firstWhere('locale', $candidate);

                    if ($row !== null) {
                        break;
                    }
                }

                if ($row !== null) {
                    $resolvedRows->put($field->name, $row);
                }
            }

            $record->setCustomFieldsCache(
                $this->resolveFromRows($fields, $resolvedRows, mergeDefaults: true),
                $this->localeResolver->valuesLocaleForEntity($entity, $locale, $record::class),
            );
        }
    }

    protected function resolveDefault(FieldDefinition $field): mixed
    {
        if (! $this->defaultValue->hasConfiguredDefault($field)) {
            return null;
        }

        $resolved = $this->defaultValue->resolveForField($field);

        if ($field->type === 'toggle') {
            return (bool) $resolved;
        }

        return $resolved;
    }
}
