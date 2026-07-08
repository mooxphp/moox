<?php

declare(strict_types=1);

namespace Moox\Builder\Registry;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Builder\Concerns\HasCustomFields;

class EntityRegistry
{
    /**
     * Memoized entity/relatable discovery. The set of Filament resources is
     * fixed once panels are booted, so scanning them once per request (this
     * singleton's lifetime) avoids repeated, expensive panel traversals.
     *
     * @var array<string, array{resource?: class-string, label?: string}>|null
     */
    protected ?array $allCache = null;

    /**
     * @var array<string, class-string>|null
     */
    protected ?array $relatableCache = null;

    /**
     * @var array<class-string<Model>, bool>
     */
    protected array $queryableModelCache = [];

    /**
     * @var array<string, bool>
     */
    protected array $tableExistsCache = [];

    /**
     * @var array<string, array<string, bool>>
     */
    protected array $tableColumnsListingCache = [];

    /**
     * @return array<string, array{resource?: class-string, label?: string}>
     */
    public function all(): array
    {
        if ($this->allCache !== null) {
            return $this->allCache;
        }

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

        return $this->allCache = $entities;
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
        if ($this->relatableCache !== null) {
            return $this->relatableCache;
        }

        $resources = [];
        $seenModels = [];

        $panelResources = $this->panelResources();
        sort($panelResources);

        // Avoid N individual information_schema queries by preloading existence
        // for all candidate model tables at once.
        $candidateModelTables = [];

        foreach ($panelResources as $resourceClass) {
            if (! method_exists($resourceClass, 'getModel')) {
                continue;
            }

            $model = $resourceClass::getModel();

            if (! is_string($model) || $model === '' || isset($candidateModelTables[$model])) {
                continue;
            }

            if (! is_subclass_of($model, Model::class)) {
                continue;
            }

            try {
                $table = (new $model)->getTable();
            } catch (\Throwable) {
                continue;
            }

            if (! filled($table)) {
                continue;
            }

            $candidateModelTables[$model] = $table;
        }

        $this->preloadTableExistence(array_values($candidateModelTables));

        foreach ($panelResources as $resourceClass) {
            if (! method_exists($resourceClass, 'getModel')) {
                continue;
            }

            $model = $resourceClass::getModel();

            if (! is_string($model) || $model === '' || isset($seenModels[$model])) {
                continue;
            }

            $table = $candidateModelTables[$model] ?? null;

            // Model table missing -> not queryable -> skip.
            if ($table === null || ! $this->tableExists($table)) {
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

        return $this->relatableCache = $resources;
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
        if (array_key_exists($modelClass, $this->queryableModelCache)) {
            return $this->queryableModelCache[$modelClass];
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            return $this->queryableModelCache[$modelClass] = false;
        }

        try {
            $table = (new $modelClass)->getTable();

            return $this->queryableModelCache[$modelClass] = filled($table) && $this->tableExists($table);
        } catch (\Throwable) {
            return $this->queryableModelCache[$modelClass] = false;
        }
    }

    protected function tableExists(string $table): bool
    {
        return $this->tableExistsCache[$table] ??= Schema::hasTable($table);
    }

    /**
     * @param  array<int, string>  $tables
     */
    protected function preloadTableExistence(array $tables): void
    {
        $tables = array_values(array_unique(array_filter($tables, static fn (mixed $table): bool => filled($table))));

        if ($tables === []) {
            return;
        }

        // The information_schema approach is MySQL-specific. In tests we often
        // run against SQLite where information_schema tables do not exist.
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            foreach ($tables as $table) {
                $this->tableExistsCache[$table] = Schema::hasTable($table);
            }

            return;
        }

        foreach (array_chunk($tables, 200) as $chunk) {
            $existingTables = DB::table('information_schema.tables')
                ->selectRaw('TABLE_NAME as table_name')
                ->whereRaw('TABLE_SCHEMA = schema()')
                ->whereIn('TABLE_TYPE', ['BASE TABLE', 'SYSTEM VERSIONED'])
                ->whereIn('TABLE_NAME', $chunk)
                ->pluck('table_name')
                ->all();

            $existingLookup = array_fill_keys($existingTables, true);

            foreach ($chunk as $table) {
                $this->tableExistsCache[$table] = isset($existingLookup[$table]);
            }
        }
    }

    public function databaseTableExists(string $table): bool
    {
        return $this->tableExists($table);
    }

    public function databaseTableHasColumn(string $table, string $column): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        if (! isset($this->tableColumnsListingCache[$table])) {
            try {
                $columns = Schema::getColumnListing($table);
                $this->tableColumnsListingCache[$table] = array_fill_keys($columns, true);
            } catch (\Throwable) {
                $this->tableColumnsListingCache[$table] = [];
            }
        }

        return isset($this->tableColumnsListingCache[$table][$column]);
    }

    /**
     * @var array<class-string, bool>
     */
    protected static array $usesCustomFieldsCache = [];

    /**
     * @param  class-string  $resourceClass
     */
    public function usesCustomFields(string $resourceClass): bool
    {
        return self::$usesCustomFieldsCache[$resourceClass] ??= in_array(
            HasCustomFields::class,
            class_uses_recursive($resourceClass),
            true,
        );
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
