<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Concerns\HasCustomFields;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\MediaIntegration;
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
     * @return Collection<int, FieldDefinition>
     */
    public function fieldsForEntity(string $entity): Collection
    {
        $groups = $this->definitionRegistry->fieldGroupsFor(new LocationContext($entity));

        return $groups->flatMap(fn ($group) => $this->storableFieldCollector->definitionsFromList($group->fields))->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function loadForModel(Model $record, string $entity, bool $fresh = false): array
    {
        $fields = $this->fieldsForEntity($entity);

        if ($fields->isEmpty()) {
            return [];
        }

        if ($fresh) {
            return $this->loadValues($entity, $record, $fields);
        }

        return $this->loadCachedValues($entity, $record, $fields);
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
            $values[$field->name] = $fieldType->castValue($raw, $field);
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
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, mixed>
     */
    public function loadValuesWithDefaults(string $entity, Model $record, Collection $fields): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $values = $this->loadValues($entity, $record, $fields);

        return app(BuilderValuesResolver::class)->mergeDefaults($fields, $values);
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, mixed>
     */
    public function loadCachedValuesWithDefaults(string $entity, Model $record, Collection $fields): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $cacheKey = "{$entity}:{$record->getKey()}";

        if (! array_key_exists($cacheKey, $this->valuesCache)) {
            $this->valuesCache[$cacheKey] = $this->loadValuesWithDefaults($entity, $record, $fields);
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
        $defaultValue = app(DefaultValue::class);
        $entity = $this->locationContextForResource($resourceClass)->entity;

        foreach ($fields as $field) {
            $fieldType = $this->fieldTypeRegistry->get($field->type);

            if (! $fieldType->storesValue()) {
                continue;
            }

            if (! array_key_exists($field->name, $data)) {
                if ($defaultValue->hasConfiguredDefault($field)) {
                    $values[$field->name] = $defaultValue->resolveForField($field);
                }

                continue;
            }

            $value = $data[$field->name];

            $hasStoredValue = $record->exists
                && FieldValue::query()
                    ->forRecord($entity, $record->getKey())
                    ->where('field_name', $field->name)
                    ->exists();

            if (! $hasStoredValue && $defaultValue->hasConfiguredDefault($field)) {
                if ($field->type === 'toggle' || $defaultValue->shouldApplyDefault($value, $field->type)) {
                    $resolved = $defaultValue->resolveForField($field);

                    if ($field->type === 'toggle' || $resolved !== null) {
                        $value = $resolved;
                    }
                }
            }

            $values[$field->name] = $value;
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

            $persisted = app(BuilderValuesResolver::class)->persistFieldValue($field, $value);
            $columns = TypedValueColumns::attributesFor($field->type, $persisted);

            FieldValue::query()->updateOrCreate(
                [
                    'entity' => $entity,
                    'record_id' => $record->getKey(),
                    'field_name' => $field->name,
                ],
                $columns,
            );
        }

        if (MediaIntegration::isAvailable()) {
            app(BuilderMediaUsageSync::class)->syncForRecord($entity, $record, $fields);
        }

        unset($this->valuesCache["{$entity}:{$record->getKey()}"]);
    }

    public function forgetValuesCache(string $entity, int|string $recordId): void
    {
        unset($this->valuesCache["{$entity}:{$recordId}"]);
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function usesCustomFields(string $resourceClass): bool
    {
        return in_array(HasCustomFields::class, class_uses_recursive($resourceClass), true);
    }
}
