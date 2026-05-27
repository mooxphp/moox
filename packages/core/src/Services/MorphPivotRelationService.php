<?php

declare(strict_types=1);

namespace Moox\Core\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Moox\Core\Support\MorphPivot\MorphPivotRelationRegistry;
use RuntimeException;

/**
 * Config-driven morph pivot relations ({resource}.morph_relations.*).
 * Same shape as address.relations.addressables: morph_name, pivot_table,
 * pivot_model, pivot_columns, relationship, model (owner side), primary.
 */
class MorphPivotRelationService
{
    private ?string $currentResource = null;

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $cached = [];

    public function setCurrentResource(string $resource): void
    {
        $this->currentResource = $resource;
    }

    public function getCurrentResource(): ?string
    {
        return $this->currentResource;
    }

    private function ensureResourceIsSet(): void
    {
        if ($this->currentResource === null) {
            throw new RuntimeException('Current resource is not set. Call setCurrentResource() first.');
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getMorphPivotRelations(): array
    {
        $this->ensureResourceIsSet();

        $resourceName = $this->currentResource;

        if (isset($this->cached[$resourceName])) {
            return $this->cached[$resourceName];
        }

        /** @var array<string, array<string, mixed>> $relations */
        $relations = config("{$resourceName}.morph_relations", []);

        $this->cached[$resourceName] = $relations;

        return $relations;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMorphPivotRelationConfig(string $relation): array
    {
        $config = $this->getMorphPivotRelations()[$relation] ?? [];

        return is_array($config) ? MorphPivotRelationRegistry::mergeConfig($config) : [];
    }

    /**
     * @return class-string|null
     */
    public function getMorphPivotRelationModel(string $relation): ?string
    {
        $model = $this->getMorphPivotRelationConfig($relation)['model'] ?? null;

        if (! is_string($model) || $model === '' || ! class_exists($model)) {
            return null;
        }

        return $model;
    }

    public function validateMorphPivotRelation(string $relation): void
    {
        if ($this->getMorphPivotRelationModel($relation) === null) {
            throw new InvalidArgumentException(sprintf(
                'Invalid or missing model for morph pivot relation [%s] on resource [%s].',
                $relation,
                $this->currentResource,
            ));
        }
    }

    public function getMorphName(string $relation): string
    {
        $config = $this->getMorphPivotRelationConfig($relation);

        if (isset($config['morph_name']) && is_string($config['morph_name']) && $config['morph_name'] !== '') {
            return $config['morph_name'];
        }

        return (string) ($config['relationship'] ?? 'addressable');
    }

    public function getPivotTable(string $relation): string
    {
        $config = $this->getMorphPivotRelationConfig($relation);

        if (isset($config['pivot_table']) && is_string($config['pivot_table']) && $config['pivot_table'] !== '') {
            return $config['pivot_table'];
        }

        return (string) ($config['table'] ?? 'addressables');
    }

    public function getForeignKey(string $relation): string
    {
        $config = $this->getMorphPivotRelationConfig($relation);

        if (isset($config['foreignKey']) && is_string($config['foreignKey']) && $config['foreignKey'] !== '') {
            return $config['foreignKey'];
        }

        return $this->getMorphName($relation).'_id';
    }

    public function getRelatedKey(string $relation): string
    {
        $config = $this->getMorphPivotRelationConfig($relation);

        if (isset($config['related_key']) && is_string($config['related_key']) && $config['related_key'] !== '') {
            return $config['related_key'];
        }

        if (isset($config['relatedKey']) && is_string($config['relatedKey']) && $config['relatedKey'] !== '') {
            return $config['relatedKey'];
        }

        $model = $this->getMorphPivotRelationModel($relation);

        if ($model !== null) {
            return Str::snake(class_basename($model)).'_id';
        }

        return 'related_id';
    }

    public function getRelationshipMethodName(string $relation): string
    {
        $config = $this->getMorphPivotRelationConfig($relation);
        $name = $config['relationship'] ?? null;

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return $relation;
    }

    /**
     * @return list<string>
     */
    public function getMorphPivotRelationPivotColumns(string $relation): array
    {
        $columns = $this->getMorphPivotRelationConfig($relation)['pivot_columns'] ?? [];

        if (! is_array($columns)) {
            return [];
        }

        if (array_is_list($columns)) {
            return array_values(array_map(strval(...), $columns));
        }

        return array_keys($columns);
    }

    /**
     * @return class-string|null
     */
    public function getMorphPivotRelationPivotModel(string $relation): ?string
    {
        $model = $this->getMorphPivotRelationConfig($relation)['pivot_model'] ?? null;

        if (! is_string($model) || $model === '' || ! class_exists($model)) {
            return null;
        }

        return $model;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPrimaryConfig(string $relation): array
    {
        $primary = $this->getMorphPivotRelationConfig($relation)['primary'] ?? [];

        return is_array($primary) ? $primary : [];
    }

    public function getPrimaryOn(string $relation): string
    {
        $primary = $this->getPrimaryConfig($relation);

        if (isset($primary['on']) && is_string($primary['on']) && $primary['on'] !== '') {
            return $primary['on'];
        }

        $column = (string) ($primary['column'] ?? 'id');

        return in_array($column, ['id', 'is_primary'], true) ? 'related' : 'pivot';
    }

    public function getPrimaryColumn(string $relation): string
    {
        return (string) ($this->getPrimaryConfig($relation)['column'] ?? 'id');
    }

    public function getPrimaryValue(string $relation, mixed $default = true): mixed
    {
        return $this->getPrimaryConfig($relation)['value'] ?? $default;
    }

    /**
     * Resolved column on the related model (e.g. id → is_primary for Address).
     */
    public function getPrimaryRelatedColumn(string $relation): string
    {
        $column = $this->getPrimaryColumn($relation);

        if ($column === 'id') {
            return 'is_primary';
        }

        return $column;
    }

    public function hasMorphPivotRelations(): bool
    {
        $this->ensureResourceIsSet();

        return $this->getMorphPivotRelations() !== [];
    }
}
