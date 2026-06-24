<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Concerns\HasCustomFields;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\OptionValueRules;
use Moox\Builder\Support\StorableFieldCollector;
use Moox\Builder\Support\TypedValueColumns;

class CustomFieldsManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $valuesCache = [];

    public function __construct(
        protected DefinitionRegistry $definitionRegistry,
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected FieldValueValidator $fieldValueValidator,
        protected StorableFieldCollector $storableFieldCollector,
    ) {}

    /**
     * @param  class-string  $resourceClass
     */
    public function locationContextForResource(string $resourceClass): LocationContext
    {
        return new LocationContext($resourceClass::resolveCustomFieldsEntityIdentifier());
    }

    /**
     * @param  class-string  $resourceClass
     * @return Collection<int, FieldDefinition>
     */
    public function fieldsForResource(string $resourceClass): Collection
    {
        $groups = $this->definitionRegistry->fieldGroupsFor(
            $this->locationContextForResource($resourceClass),
        );

        return $groups->flatMap(fn ($group) => $this->storableFieldCollector->definitionsFromList($group->fields))->values();
    }

    /**
     * @param  class-string  $resourceClass
     * @return array<string, mixed>
     */
    public function loadFormData(string $resourceClass, Model $record): array
    {
        $fields = $this->fieldsForResource($resourceClass);

        if ($fields->isEmpty()) {
            return [];
        }

        return $this->loadValues(
            $this->locationContextForResource($resourceClass)->entity,
            $record,
            $fields,
        );
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, mixed>
     */
    public function loadValues(string $entity, Model $record, Collection $fields): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $rows = FieldValue::query()
            ->forRecord($entity, $record->getKey())
            ->whereIn('field_name', $fields->pluck('name'))
            ->get()
            ->keyBy('field_name');

        $values = [];

        foreach ($fields as $field) {
            $fieldType = $this->fieldTypeRegistry->get($field->type);

            if (! $fieldType->storesValue()) {
                continue;
            }

            $row = $rows->get($field->name);

            if ($row === null) {
                continue;
            }

            $raw = TypedValueColumns::read($row, $field->type);
            $values[$field->name] = $fieldType->castValue($raw);
        }

        return $values;
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, mixed>
     */
    public function loadCachedValues(string $entity, Model $record, Collection $fields): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $cacheKey = "{$entity}:{$record->getKey()}";

        if (! array_key_exists($cacheKey, $this->valuesCache)) {
            $this->valuesCache[$cacheKey] = $this->loadValues($entity, $record, $fields);
        }

        return $this->valuesCache[$cacheKey];
    }

    /**
     * @param  class-string  $resourceClass
     * @param  array<string, mixed>  $data
     */
    public function saveFromFormData(string $resourceClass, Model $record, array $data): void
    {
        $fields = $this->fieldsForResource($resourceClass);

        if ($fields->isEmpty()) {
            return;
        }

        $values = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field->name, $data)) {
                continue;
            }

            if (! $this->fieldTypeRegistry->get($field->type)->storesValue()) {
                continue;
            }

            $values[$field->name] = $data[$field->name];
        }

        if ($values === []) {
            return;
        }

        $this->saveValues(
            $this->locationContextForResource($resourceClass)->entity,
            $record,
            $values,
            $fields,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  Collection<int, FieldDefinition>  $fields
     */
    public function saveValues(string $entity, Model $record, array $values, Collection $fields): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field->name, $values)) {
                continue;
            }

            $fieldType = $this->fieldTypeRegistry->get($field->type);

            if (! $fieldType->storesValue()) {
                continue;
            }

            $value = $values[$field->name];

            OptionValueRules::assertValid($field, $value);

            $this->fieldValueValidator->assertValid($field, $value);

            $cast = $fieldType->castValue($value);
            $columns = TypedValueColumns::attributesFor($field->type, $cast);

            FieldValue::query()->updateOrCreate(
                [
                    'entity' => $entity,
                    'record_id' => $record->getKey(),
                    'field_name' => $field->name,
                ],
                $columns,
            );
        }

        unset($this->valuesCache["{$entity}:{$record->getKey()}"]);
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function usesCustomFields(string $resourceClass): bool
    {
        return in_array(HasCustomFields::class, class_uses_recursive($resourceClass), true);
    }
}
