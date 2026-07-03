<?php

declare(strict_types=1);

namespace Moox\Builder\Registry;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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
     * All Filament resources that can be a relation target, keyed by a stable
     * identifier. Unlike all(), this is not limited to resources that host
     * custom fields, so relations can point to any Moox entity (e.g. users).
     *
     * @return array<string, class-string>
     */
    public function relatableResources(): array
    {
        $resources = [];
        $seenModels = [];

        $panelResources = $this->panelResources();
        sort($panelResources);

        foreach ($panelResources as $resourceClass) {
            if (! method_exists($resourceClass, 'getModel')) {
                continue;
            }

            $model = $resourceClass::getModel();

            if (! is_string($model) || $model === '' || ! $this->modelIsQueryable($model)) {
                continue;
            }

            // A relation always points at a model, so multiple resources that
            // expose the same model represent the same target: keep only one.
            if (isset($seenModels[$model])) {
                continue;
            }

            $key = $this->relatableKeyFor($resourceClass);

            if ($key === '') {
                continue;
            }

            if (isset($resources[$key]) && $resources[$key] !== $resourceClass) {
                $key .= '_'.Str::of($model)->classBasename()->snake()->toString();
            }

            $seenModels[$model] = true;
            $resources[$key] = $resourceClass;
        }

        ksort($resources);

        return $resources;
    }

    /**
     * @return array<string, string>
     */
    public function relatableOptions(): array
    {
        $resources = $this->relatableResources();

        $labels = [];

        foreach ($resources as $key => $resourceClass) {
            $labels[$key] = $this->labelForResource($resourceClass);
        }

        // Distinct targets may share a label (e.g. two "Categories" models from
        // different packages). Qualify collisions so every option is unambiguous.
        $duplicates = array_keys(array_filter(array_count_values($labels), fn (int $count): bool => $count > 1));

        foreach ($labels as $key => $label) {
            if (in_array($label, $duplicates, true)) {
                $labels[$key] = $label.' ('.$this->relatableQualifier($resources[$key]).')';
            }
        }

        asort($labels);

        return $labels;
    }

    /**
     * @return class-string|null
     */
    public function relatedModelFor(string $key): ?string
    {
        $resourceClass = $this->relatableResources()[$key] ?? null;

        if ($resourceClass === null || ! method_exists($resourceClass, 'getModel')) {
            return null;
        }

        $model = $resourceClass::getModel();

        return is_string($model) ? $model : null;
    }

    /**
     * @return class-string|null
     */
    public function relatedResourceFor(string $key): ?string
    {
        return $this->relatableResources()[$key] ?? null;
    }

    /**
     * Whether the model's database table exists and can be queried safely.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function modelIsQueryable(string $modelClass): bool
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            return false;
        }

        try {
            $table = (new $modelClass)->getTable();

            return filled($table) && Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
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
        return array_values(array_filter(
            $this->panelResources(),
            fn (string $resourceClass): bool => $this->usesCustomFields($resourceClass),
        ));
    }

    /**
     * @return list<class-string>
     */
    protected function panelResources(): array
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
                $resources[] = $resourceClass;
            }
        }

        return array_values(array_unique($resources));
    }

    /**
     * @param  class-string  $resourceClass
     */
    protected function relatableKeyFor(string $resourceClass): string
    {
        $entity = $this->resolveEntityKey($resourceClass);

        if (is_string($entity) && $entity !== '') {
            return $entity;
        }

        return Str::of(class_basename($resourceClass))
            ->beforeLast('Resource')
            ->snake()
            ->toString();
    }

    /**
     * A human-readable qualifier used to disambiguate resources that share a
     * label, derived from the owning package/namespace segment.
     *
     * @param  class-string  $resourceClass
     */
    protected function relatableQualifier(string $resourceClass): string
    {
        $segments = explode('\\', $resourceClass);

        // e.g. Moox\Press\Resources\CategoryResource => "Press",
        //      App\Filament\Resources\CategoryResource => "App".
        $segment = $segments[1] ?? $segments[0];

        return Str::headline($segment);
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
