<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\ConditionalLogic;
use Moox\Builder\Support\FieldVisibility;
use Moox\Builder\Support\MediaIntegration;
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
        protected BuilderLocaleResolver $localeResolver,
        protected EntityRegistry $entityRegistry,
        protected ClonedFieldGroupResolver $clonedFieldGroupResolver,
    ) {}

    /**
     * @param  class-string  $resourceClass
     */
    public function locationContextForResource(string $resourceClass): LocationContext
    {
        return LocationContext::forResource($resourceClass);
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
     * Storable fields of an entity, filtered to those visible in the given
     * context (e.g. FieldVisibility::API for JsonResource output).
     *
     * @return Collection<int, FieldDefinition>
     */
    public function visibleFieldsForEntity(string $entity, string $context): Collection
    {
        $groups = FieldVisibility::filterGroups(
            $this->definitionRegistry->fieldGroupsFor(new LocationContext($entity)),
            $context,
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
    public function loadValues(string $entity, Model $record, Collection $fields, ?string $locale = null): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $localeChain = $this->localeResolver->valuesFallbackChainForEntity($entity, $locale, $record::class);

        $rows = FieldValue::query()
            ->forRecord($entity, $record->getKey())
            ->whereIn('field_name', $fields->pluck('name'))
            ->whereIn('locale', $localeChain)
            ->get()
            ->groupBy('field_name');

        $values = [];

        foreach ($fields as $field) {
            $fieldType = $this->fieldTypeRegistry->get($field->type);

            if (! $fieldType->storesValue()) {
                continue;
            }

            $row = $this->resolveRowForLocale($rows->get($field->name, collect()), $localeChain);

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
    public function loadCachedValues(string $entity, Model $record, Collection $fields, ?string $locale = null): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $cacheKey = $this->valuesCacheKey($entity, $record->getKey(), $locale);

        if (! array_key_exists($cacheKey, $this->valuesCache)) {
            $this->valuesCache[$cacheKey] = $this->loadValues($entity, $record, $fields, $locale);
        }

        return $this->valuesCache[$cacheKey];
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, mixed>
     */
    public function loadValuesWithDefaults(string $entity, Model $record, Collection $fields, ?string $locale = null): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $values = $this->loadValues($entity, $record, $fields, $locale);

        return app(BuilderValuesResolver::class)->mergeDefaults($fields, $values);
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, mixed>
     */
    public function loadCachedValuesWithDefaults(string $entity, Model $record, Collection $fields, ?string $locale = null): array
    {
        if ($fields->isEmpty()) {
            return [];
        }

        $cacheKey = $this->valuesCacheKey($entity, $record->getKey(), $locale);

        if (! array_key_exists($cacheKey, $this->valuesCache)) {
            $this->valuesCache[$cacheKey] = $this->loadValuesWithDefaults($entity, $record, $fields, $locale);
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
        $locale = $this->localeResolver->valuesLocaleForResource($resourceClass);

        // Only trust submitted values for fields that are actually part of the
        // admin form. Admin-hidden fields must not be writable via crafted
        // requests; they still receive their configured defaults below.
        $adminVisibleNames = array_flip(
            $this->visibleFieldsForEntity($entity, FieldVisibility::ADMIN)->pluck('name')->all(),
        );
        $data = array_intersect_key($data, $adminVisibleNames);

        // Load the already-stored field names once instead of issuing an
        // exists() query per field when deciding whether to apply defaults.
        $storedFieldNames = $record->exists
            ? array_flip(
                FieldValue::query()
                    ->forRecord($entity, $record->getKey())
                    ->forLocale($locale)
                    ->pluck('field_name')
                    ->all(),
            )
            : [];

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

            $hasStoredValue = isset($storedFieldNames[$field->name]);

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

        $values = $this->preserveAdminHiddenNestedValues($entity, $record, $fields, $values, $locale);

        $this->saveValues(
            $this->locationContextForResource($resourceClass)->entity,
            $record,
            $values,
            $fields,
            $locale,
        );
    }

    /**
     * Nested fields with visible_admin:false are not rendered in the form, but
     * their keys can still appear in a crafted compound payload. Replace those
     * keys with the already-stored values so the form path cannot overwrite them.
     *
     * @param  Collection<int, FieldDefinition>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function preserveAdminHiddenNestedValues(
        string $entity,
        Model $record,
        Collection $fields,
        array $values,
        string $locale,
    ): array {
        $existing = $record->exists
            ? $this->loadCachedValues($entity, $record, $fields, $locale)
            : [];

        foreach ($fields as $field) {
            if (! array_key_exists($field->name, $values)) {
                continue;
            }

            if (! $this->fieldTypeRegistry->get($field->type)->hasSubFields()) {
                continue;
            }

            $children = $field->type === 'clone'
                ? $this->clonedFieldGroupResolver->compoundChildren($field)
                : ($field->type === 'flexible_content' ? $field->layouts() : $field->children);

            if ($children->isEmpty()) {
                continue;
            }

            $values[$field->name] = FieldVisibility::mergePreservingHidden(
                $field,
                $values[$field->name],
                $existing[$field->name] ?? null,
                FieldVisibility::ADMIN,
                $children,
            );
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  Collection<int, FieldDefinition>  $fields
     */
    public function saveValues(string $entity, Model $record, array $values, Collection $fields, ?string $locale = null): void
    {
        $locale = $this->localeResolver->valuesLocaleForEntity($entity, $locale, $record::class);

        foreach ($fields as $field) {
            if (! array_key_exists($field->name, $values)) {
                continue;
            }

            $fieldType = $this->fieldTypeRegistry->get($field->type);

            if (! $fieldType->storesValue()) {
                continue;
            }

            $value = $values[$field->name];

            if (ConditionalLogic::isVisibleForValues($field, $values)) {
                $this->fieldValueValidator->assertValid($field, $value, $record);
            } else {
                // A field hidden by conditional logic must never accept the
                // submitted (untrusted, unvalidated) value. Clear it instead of
                // persisting whatever the request contained.
                $value = null;
            }

            $persisted = app(BuilderValuesResolver::class)->persistFieldValue($field, $value);
            $columns = TypedValueColumns::attributesFor($field->type, $persisted);

            FieldValue::query()->updateOrCreate(
                [
                    'entity' => $entity,
                    'record_id' => $record->getKey(),
                    'field_name' => $field->name,
                    'locale' => $locale,
                ],
                $columns,
            );
        }

        if (MediaIntegration::isAvailable()) {
            app(BuilderMediaUsageSync::class)->syncForRecord($entity, $record, $fields);
        }

        $this->forgetValuesCache($entity, $record->getKey(), $locale);
    }

    public function forgetValuesCache(string $entity, int|string $recordId, ?string $locale = null): void
    {
        if ($locale !== null) {
            unset($this->valuesCache[$this->valuesCacheKey($entity, $recordId, $locale)]);

            return;
        }

        foreach (array_keys($this->valuesCache) as $cacheKey) {
            if (str_starts_with($cacheKey, "{$entity}:{$recordId}:")) {
                unset($this->valuesCache[$cacheKey]);
            }
        }
    }

    /**
     * @param  Collection<int, FieldValue>  $rows
     * @param  list<string>  $localeChain
     */
    protected function resolveRowForLocale(Collection $rows, array $localeChain): ?FieldValue
    {
        foreach ($localeChain as $locale) {
            $row = $rows->firstWhere('locale', $locale);

            if ($row instanceof FieldValue) {
                return $row;
            }
        }

        return null;
    }

    protected function valuesCacheKey(string $entity, int|string $recordId, ?string $locale = null): string
    {
        return "{$entity}:{$recordId}:".$this->localeResolver->valuesLocaleForEntity($entity, $locale);
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function usesCustomFields(string $resourceClass): bool
    {
        return $this->entityRegistry->usesCustomFields($resourceClass);
    }
}
