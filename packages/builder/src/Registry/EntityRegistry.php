<?php

declare(strict_types=1);

namespace Moox\Builder\Registry;

use Illuminate\Support\Str;

class EntityRegistry
{
    /**
     * @return array<string, array{resource?: class-string, label?: string}>
     */
    public function all(): array
    {
        return config('builder.entities', []);
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function resolveForResource(string $resourceClass): ?string
    {
        foreach ($this->all() as $key => $definition) {
            if (($definition['resource'] ?? null) === $resourceClass) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * @return class-string|null
     */
    public function resourceFor(string $entity): ?string
    {
        $resource = $this->all()[$entity]['resource'] ?? null;

        return is_string($resource) ? $resource : null;
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
        foreach ($this->all() as $key => $definition) {
            $resource = $definition['resource'] ?? null;

            if (! is_string($resource) || ! method_exists($resource, 'getModel')) {
                continue;
            }

            if ($resource::getModel() === $modelClass) {
                return (string) $key;
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

        foreach (array_keys($this->all()) as $entity) {
            $options[$entity] = $this->labelFor($entity);
        }

        return $options;
    }

    /**
     * @param  class-string  $resourceClass
     */
    public function isRegisteredResource(string $resourceClass): bool
    {
        return $this->resolveForResource($resourceClass) !== null;
    }
}
