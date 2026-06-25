<?php

declare(strict_types=1);

namespace Moox\Builder\Registry;

use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Moox\Builder\Concerns\HasCustomFields;

class EntityRegistry
{
    /**
     * @return array<string, array{resource?: class-string, label?: string}>
     */
    public function all(): array
    {
        $entities = [];

        foreach ($this->traitResources() as $resourceClass) {
            $entity = $this->resolveEntityKey($resourceClass);

            if ($entity === null) {
                continue;
            }

            $entities[$entity] = [
                'resource' => $resourceClass,
                'label' => $this->labelForResource($resourceClass),
            ];
        }

        return $entities;
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function resolveForResource(string $resourceClass): ?string
    {
        if (! $this->usesCustomFields($resourceClass)) {
            return null;
        }

        return $this->resolveEntityKey($resourceClass);
    }

    /**
     * @return class-string|null
     */
    public function resourceFor(string $entity): ?string
    {
        foreach ($this->all() as $key => $definition) {
            if ($key === $entity) {
                $resource = $definition['resource'] ?? null;

                return is_string($resource) ? $resource : null;
            }
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    public function modelFor(string $entity): ?string
    {
        $resource = $this->resourceFor($entity);

        if ($resource === null || ! method_exists($resource, 'getModel')) {
            return null;
        }

        $model = $resource::getModel();

        return is_string($model) ? $model : null;
    }

    /**
     * @param  class-string  $modelClass
     */
    public function entityForModel(string $modelClass): ?string
    {
        foreach ($this->all() as $entity => $definition) {
            $resource = $definition['resource'] ?? null;

            if (! is_string($resource) || ! method_exists($resource, 'getModel')) {
                continue;
            }

            if ($resource::getModel() === $modelClass) {
                return $entity;
            }
        }

        return null;
    }

    public function labelFor(string $entity): string
    {
        $definition = $this->all()[$entity] ?? [];

        if (filled($definition['label'] ?? null)) {
            return (string) $definition['label'];
        }

        return Str::headline($entity);
    }

    /**
     * @param  list<string>  $entities
     */
    public function labelsFor(array $entities): string
    {
        if ($entities === []) {
            return '—';
        }

        return collect($entities)
            ->map(fn (string $entity): string => $this->labelFor($entity))
            ->implode(', ');
    }

    /**
     * @return array<string, string>
     */
    public function optionsForSelect(): array
    {
        $options = [];

        foreach ($this->all() as $entity => $definition) {
            $options[$entity] = $definition['label'] ?? $this->labelFor($entity);
        }

        return $options;
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function usesCustomFields(string $resourceClass): bool
    {
        return in_array(HasCustomFields::class, class_uses_recursive($resourceClass), true);
    }

    /**
     * @param  class-string  $resourceClass
     */
    protected function resolveEntityKey(string $resourceClass): ?string
    {
        if (! method_exists($resourceClass, 'resolveCustomFieldsEntityIdentifier')) {
            return null;
        }

        return $resourceClass::resolveCustomFieldsEntityIdentifier();
    }

    /**
     * @return list<class-string>
     */
    protected function traitResources(): array
    {
        if (! class_exists(Filament::class)) {
            return [];
        }

        try {
            $panels = Filament::getPanels();
        } catch (\Throwable) {
            return [];
        }

        $resources = [];

        foreach ($panels as $panel) {
            foreach ($panel->getResources() as $resourceClass) {
                if ($this->usesCustomFields($resourceClass)) {
                    $resources[] = $resourceClass;
                }
            }
        }

        return array_values(array_unique($resources));
    }

    /**
     * @param  class-string  $resourceClass
     */
    protected function labelForResource(string $resourceClass): string
    {
        if (method_exists($resourceClass, 'getPluralModelLabel')) {
            return (string) $resourceClass::getPluralModelLabel();
        }

        return Str::headline(class_basename($resourceClass));
    }
}
