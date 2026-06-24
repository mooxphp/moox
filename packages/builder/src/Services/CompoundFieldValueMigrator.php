<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldValue;

class CompoundFieldValueMigrator
{
    public function __construct(
        protected FieldValuePurger $fieldValuePurger,
    ) {}

    /**
     * @param  list<string>  $entities
     */
    public function renameNestedSubfield(Field $field, string $previousName, array $entities): void
    {
        $context = $this->resolveStorageContext($field);

        if ($context === null) {
            $this->fieldValuePurger->purgeForFieldName($previousName, $entities);

            return;
        }

        $this->mutateStoredValues(
            $entities,
            $context->compoundFieldName,
            fn (mixed $value) => $this->renameKeyInValue($value, $context, $previousName, $field->name),
        );
    }

    /**
     * @param  list<string>  $entities
     */
    public function removeNestedSubfield(Field $field, array $entities): void
    {
        $context = $this->resolveStorageContext($field);

        if ($context === null) {
            $this->fieldValuePurger->purgeForFieldName($field->name, $entities);

            return;
        }

        $this->mutateStoredValues(
            $entities,
            $context->compoundFieldName,
            fn (mixed $value) => $this->removeKeyInValue($value, $context, $field->name),
        );
    }

    /**
     * @param  list<string>  $entities
     * @param  callable(mixed): mixed  $mutator
     */
    protected function mutateStoredValues(array $entities, string $compoundFieldName, callable $mutator): void
    {
        if ($entities === []) {
            return;
        }

        FieldValue::query()
            ->whereIn('entity', $entities)
            ->where('field_name', $compoundFieldName)
            ->whereNotNull('value_json')
            ->each(function (FieldValue $row) use ($mutator): void {
                $updated = $mutator($row->value_json);

                if ($updated === $row->value_json) {
                    return;
                }

                if ($this->isEmptyCompoundValue($updated)) {
                    $row->delete();

                    return;
                }

                $row->update(['value_json' => $updated]);
            });
    }

    protected function resolveStorageContext(Field $field): ?CompoundStorageContext
    {
        $field->loadMissing('parentField.parentField');

        $parent = $field->parentField;

        if ($parent === null) {
            return null;
        }

        if (in_array($parent->type, ['group', 'repeater'], true)) {
            return new CompoundStorageContext($parent->name, $parent->type);
        }

        if ($parent->type === 'flexible_layout') {
            $flexParent = $parent->parentField;

            if ($flexParent === null || $flexParent->type !== 'flexible_content') {
                return null;
            }

            return new CompoundStorageContext(
                compoundFieldName: $flexParent->name,
                compoundType: 'flexible_content',
                layoutName: $parent->name,
            );
        }

        return null;
    }

    protected function renameKeyInValue(mixed $value, CompoundStorageContext $context, string $from, string $to): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        return match ($context->compoundType) {
            'group' => $this->renameKeyInGroupValue($value, $from, $to),
            'repeater' => $this->renameKeyInRepeaterValue($value, $from, $to),
            'flexible_content' => $this->renameKeyInFlexibleContentValue($value, $context->layoutName, $from, $to),
            default => $value,
        };
    }

    protected function removeKeyInValue(mixed $value, CompoundStorageContext $context, string $key): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        return match ($context->compoundType) {
            'group' => $this->removeKeyInGroupValue($value, $key),
            'repeater' => $this->removeKeyInRepeaterValue($value, $key),
            'flexible_content' => $this->removeKeyInFlexibleContentValue($value, $context->layoutName, $key),
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    protected function renameKeyInGroupValue(array $value, string $from, string $to): array
    {
        if (array_is_list($value) && isset($value[0]) && is_array($value[0])) {
            $value[0] = $this->renameKeyInAssociativeArray($value[0], $from, $to);

            return $value;
        }

        return $this->renameKeyInAssociativeArray($value, $from, $to);
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    protected function removeKeyInGroupValue(array $value, string $key): array
    {
        if (array_is_list($value) && isset($value[0]) && is_array($value[0])) {
            $value[0] = $this->removeKeyFromAssociativeArray($value[0], $key);

            return $value;
        }

        return $this->removeKeyFromAssociativeArray($value, $key);
    }

    /**
     * @param  list<array<string, mixed>>  $value
     * @return list<array<string, mixed>>
     */
    protected function renameKeyInRepeaterValue(array $value, string $from, string $to): array
    {
        foreach ($value as $index => $item) {
            if (is_array($item)) {
                $value[$index] = $this->renameKeyInAssociativeArray($item, $from, $to);
            }
        }

        return $value;
    }

    /**
     * @param  list<array<string, mixed>>  $value
     * @return list<array<string, mixed>>
     */
    protected function removeKeyInRepeaterValue(array $value, string $key): array
    {
        foreach ($value as $index => $item) {
            if (is_array($item)) {
                $value[$index] = $this->removeKeyFromAssociativeArray($item, $key);
            }
        }

        return $value;
    }

    /**
     * @param  list<array<string, mixed>>  $value
     * @return list<array<string, mixed>>
     */
    protected function renameKeyInFlexibleContentValue(array $value, ?string $layoutName, string $from, string $to): array
    {
        foreach ($value as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($layoutName !== null && (string) ($item['type'] ?? $item['layout'] ?? '') !== $layoutName) {
                continue;
            }

            if (isset($item['data']) && is_array($item['data'])) {
                $item['data'] = $this->renameKeyInAssociativeArray($item['data'], $from, $to);
            }

            $value[$index] = $item;
        }

        return $value;
    }

    /**
     * @param  list<array<string, mixed>>  $value
     * @return list<array<string, mixed>>
     */
    protected function removeKeyInFlexibleContentValue(array $value, ?string $layoutName, string $key): array
    {
        foreach ($value as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($layoutName !== null && (string) ($item['type'] ?? $item['layout'] ?? '') !== $layoutName) {
                continue;
            }

            if (isset($item['data']) && is_array($item['data'])) {
                $item['data'] = $this->removeKeyFromAssociativeArray($item['data'], $key);
            }

            $value[$index] = $item;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function renameKeyInAssociativeArray(array $data, string $from, string $to): array
    {
        if (! array_key_exists($from, $data)) {
            return $data;
        }

        $data[$to] = $data[$from];
        unset($data[$from]);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function removeKeyFromAssociativeArray(array $data, string $key): array
    {
        unset($data[$key]);

        return $data;
    }

    protected function isEmptyCompoundValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        if ($value === []) {
            return true;
        }

        if (array_is_list($value)) {
            return collect($value)->every(fn (mixed $item): bool => $item === null || $item === [] || $item === '');
        }

        return collect($value)->every(fn (mixed $item): bool => $item === null || $item === '' || $item === []);
    }
}

readonly class CompoundStorageContext
{
    public function __construct(
        public string $compoundFieldName,
        public string $compoundType,
        public ?string $layoutName = null,
    ) {}
}
