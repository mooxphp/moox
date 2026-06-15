<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Moox\Builder\Concerns\HasCustomFields;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Storage\ValueStoreResolver;

class CustomFieldsManager
{
    public function __construct(
        protected DefinitionRegistry $definitionRegistry,
        protected ValueStoreResolver $valueStoreResolver,
        protected EntityRegistry $entityRegistry,
    ) {}

    /**
     * @param  class-string  $resourceClass
     */
    public function locationContextForResource(string $resourceClass): LocationContext
    {
        if (in_array(HasCustomFields::class, class_uses_recursive($resourceClass), true)) {
            return $resourceClass::customFieldsLocationContext();
        }

        $modelClass = $resourceClass::getModel();

        return new LocationContext(Str::kebab(class_basename($modelClass)));
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

        return $groups->flatMap(fn ($group) => $group->fields)->values();
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

        return $this->valueStoreResolver->for()->load(
            $this->locationContextForResource($resourceClass)->entity,
            $record,
            $fields,
        );
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
            if (array_key_exists($field->name, $data)) {
                $values[$field->name] = $data[$field->name];
            }
        }

        if ($values === []) {
            return;
        }

        $this->valueStoreResolver->for()->save(
            $this->locationContextForResource($resourceClass)->entity,
            $record,
            $values,
            $fields,
        );
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function usesCustomFields(string $resourceClass): bool
    {
        return in_array(HasCustomFields::class, class_uses_recursive($resourceClass), true)
            && $this->entityRegistry->isRegisteredResource($resourceClass);
    }
}
