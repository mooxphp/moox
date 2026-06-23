<?php

declare(strict_types=1);

namespace Moox\Core\Services;

use Illuminate\Support\Str;
use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationPerspective;
use Moox\Core\Relations\Enums\RelationPresentation;
use Moox\Core\Relations\Exceptions\RelationConfigurationException;
use Moox\Core\Relations\Exceptions\RelationNotFoundException;
use Moox\Core\Relations\Exceptions\RelationResourceNotSetException;
use Moox\Core\Relations\RelationConfigNormalizer;
use Moox\Core\Relations\RelationLabel;
use Moox\Core\Relations\ResolvedRelation;

class RelationService
{
    private ?string $currentResource = null;

    /** @var array<string, array<string, ResolvedRelation>> */
    private array $resolvedCache = [];

    public function forResource(string $resource): self
    {
        $this->currentResource = $resource;

        return $this;
    }

    public function setCurrentResource(string $resource): void
    {
        $this->currentResource = $resource;
    }

    public function getCurrentResource(): ?string
    {
        return $this->currentResource;
    }

    /**
     * @template TReturn
     *
     * @param  callable(self): TReturn  $callback
     * @return TReturn
     */
    public function withResource(string $resource, callable $callback): mixed
    {
        $previous = $this->currentResource;

        $this->forResource($resource);

        try {
            return $callback($this);
        } finally {
            if (is_string($previous) && $previous !== '') {
                $this->forResource($previous);
            }
        }
    }

    /**
     * @return array<string, ResolvedRelation>
     */
    public function all(): array
    {
        $this->ensureResourceIsSet();

        $resource = (string) $this->currentResource;

        if (isset($this->resolvedCache[$resource])) {
            return $this->resolvedCache[$resource];
        }

        /** @var array<string, array<string, mixed>> $merged */
        $merged = array_replace(
            RelationConfigNormalizer::fromTaxonomies(config("{$resource}.taxonomies", [])),
            RelationConfigNormalizer::fromMorphRelations(config("{$resource}.morph_relations", [])),
            collect(config("{$resource}.relations", []))
                ->filter(fn (mixed $value): bool => is_array($value))
                ->map(fn (array $config, string $key): array => RelationConfigNormalizer::normalize($key, $config))
                ->all(),
        );

        $resolved = [];

        foreach ($merged as $key => $config) {
            $resolved[$key] = $this->resolve((string) $key, $config);
        }

        return $this->resolvedCache[$resource] = $resolved;
    }

    public function get(string $key): ResolvedRelation
    {
        $relations = $this->all();

        if (! isset($relations[$key])) {
            throw RelationNotFoundException::forKey((string) $this->currentResource, $key);
        }

        return $relations[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    /**
     * @return array<string, ResolvedRelation>
     */
    public function tabRelations(): array
    {
        return array_filter(
            $this->all(),
            fn (ResolvedRelation $relation): bool => $relation->presentation === RelationPresentation::Tab,
        );
    }

    /**
     * @return array<string, ResolvedRelation>
     */
    public function inlineRelations(): array
    {
        return array_filter(
            $this->all(),
            fn (ResolvedRelation $relation): bool => $relation->presentation === RelationPresentation::Inline,
        );
    }

    /**
     * Legacy taxonomy-shaped config for inline relation form fields.
     *
     * @return array<string, array<string, mixed>>
     */
    public function inlineRelationConfigs(): array
    {
        $configs = [];

        foreach ($this->inlineRelations() as $key => $resolved) {
            $configs[$key] = array_merge($resolved->config, [
                'model' => $resolved->relatedModel,
                'table' => $resolved->pivotTable,
                'foreignKey' => $resolved->foreignKey,
                'relatedKey' => $resolved->relatedKey,
                'relationship' => $resolved->morphType ?? $resolved->relationship,
                'label' => $resolved->label(),
            ]);
        }

        return $configs;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyBelongsToCreatePrefill(array $data): array
    {
        foreach ($this->all() as $relation) {
            if ($relation->kind !== RelationKind::BelongsTo) {
                continue;
            }

            $foreignKey = $relation->foreignKey ?? $relation->config['foreign_key'] ?? null;

            if (! is_string($foreignKey) || $foreignKey === '') {
                continue;
            }

            if (array_key_exists($foreignKey, $data) && filled($data[$foreignKey])) {
                continue;
            }

            $prefill = request()->query($foreignKey);

            if (filled($prefill)) {
                $data[$foreignKey] = $prefill;
            }
        }

        return $data;
    }

    public function tabLabel(string $key, bool $inverse = false): string
    {
        $relation = $this->get($key);

        if ($inverse) {
            $inverseLabel = $relation->config['inverse_label'] ?? null;

            if (is_string($inverseLabel) && $inverseLabel !== '') {
                return $this->translateConfigLabel($inverseLabel);
            }
        }

        return $relation->label();
    }

    public function translateConfigLabel(string $label): string
    {
        return RelationLabel::resolve($label, $label);
    }

    public function relationshipMethod(string $key): string
    {
        return $this->get($key)->relationship;
    }

    /**
     * @return class-string|null
     */
    public function relatedModel(string $key): ?string
    {
        return $this->get($key)->relatedModel;
    }

    /**
     * @return list<string>
     */
    public function pivotAttributes(string $key): array
    {
        return $this->get($key)->pivotAttributes;
    }

    public function morphType(string $key): string
    {
        $relation = $this->get($key);

        return $relation->morphType ?? $relation->relationship.'able';
    }

    public function pivotTable(string $key): string
    {
        $relation = $this->get($key);

        if ($relation->pivotTable === null || $relation->pivotTable === '') {
            throw RelationConfigurationException::missing((string) $this->currentResource, $key, 'pivot_table');
        }

        return $relation->pivotTable;
    }

    public function foreignKey(string $key): string
    {
        $relation = $this->get($key);

        if ($relation->foreignKey !== null && $relation->foreignKey !== '') {
            return $relation->foreignKey;
        }

        return $this->morphType($key).'_id';
    }

    public function relatedKey(string $key): string
    {
        $relation = $this->get($key);

        if ($relation->relatedKey !== null && $relation->relatedKey !== '') {
            return $relation->relatedKey;
        }

        if ($relation->relatedModel !== null) {
            return Str::snake(class_basename($relation->relatedModel)).'_id';
        }

        return 'related_id';
    }

    /**
     * @return class-string|null
     */
    public function pivotModel(string $key): ?string
    {
        return $this->get($key)->pivotModel;
    }

    public function primaryOn(string $key): string
    {
        $primary = $this->get($key)->config['primary'] ?? [];

        if (! is_array($primary)) {
            return 'related';
        }

        if (isset($primary['on']) && is_string($primary['on']) && $primary['on'] !== '') {
            return $primary['on'];
        }

        $column = (string) ($primary['column'] ?? 'id');

        return in_array($column, ['id', 'is_primary'], true) ? 'related' : 'pivot';
    }

    public function primaryColumn(string $key): string
    {
        $primary = $this->get($key)->config['primary'] ?? [];

        return is_array($primary) ? (string) ($primary['column'] ?? 'id') : 'id';
    }

    public function primaryValue(string $key, mixed $default = true): mixed
    {
        $primary = $this->get($key)->config['primary'] ?? [];

        return is_array($primary) ? ($primary['value'] ?? $default) : $default;
    }

    public function primaryRelatedColumn(string $key): string
    {
        $column = $this->primaryColumn($key);

        return $column === 'id' ? 'is_primary' : $column;
    }

    public function validate(string $key): void
    {
        $relation = $this->get($key);

        if ($relation->kind === RelationKind::MorphPivot || $relation->kind === RelationKind::PivotHasMany) {
            if ($relation->relatedModel === null || ! class_exists($relation->relatedModel)) {
                throw RelationConfigurationException::invalidModel(
                    (string) $this->currentResource,
                    $key,
                    (string) $relation->relatedModel,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolve(string $key, array $config): ResolvedRelation
    {
        $kind = RelationKind::tryFrom((string) ($config['kind'] ?? '')) ?? RelationKind::MorphPivot;
        $perspective = RelationPerspective::tryFrom((string) ($config['perspective'] ?? '')) ?? RelationPerspective::Owner;
        $presentation = RelationPresentation::tryFrom((string) ($config['presentation'] ?? '')) ?? RelationPresentation::Tab;

        $pivotAttributes = self::listOfStrings($config['pivot_attributes'] ?? $config['pivot_columns'] ?? []);
        $displayColumns = self::listOfStrings($config['display_columns'] ?? ['name']);
        $ownerTypes = self::normalizeOwnerTypes($config['owner_types'] ?? []);

        $relatedModel = $config['related_model'] ?? $config['model'] ?? null;
        $relatedModel = is_string($relatedModel) && $relatedModel !== '' ? $relatedModel : null;

        return new ResolvedRelation(
            key: $key,
            kind: $kind,
            perspective: $perspective,
            presentation: $presentation,
            relationship: (string) ($config['relationship'] ?? $key),
            relatedModel: $relatedModel,
            relatedResource: is_string($config['related_resource'] ?? null) ? $config['related_resource'] : null,
            pivotModel: is_string($config['pivot_model'] ?? null) ? $config['pivot_model'] : null,
            pivotTable: is_string($config['pivot_table'] ?? $config['table'] ?? null) ? ($config['pivot_table'] ?? $config['table']) : null,
            morphType: is_string($config['morph_type'] ?? $config['morph_name'] ?? null) ? ($config['morph_type'] ?? $config['morph_name']) : null,
            foreignKey: is_string($config['foreign_key'] ?? $config['foreignKey'] ?? null) ? ($config['foreign_key'] ?? $config['foreignKey']) : null,
            relatedKey: is_string($config['related_key'] ?? $config['relatedKey'] ?? null) ? ($config['related_key'] ?? $config['relatedKey']) : null,
            inverseRelationship: is_string($config['inverse_relationship'] ?? null) ? $config['inverse_relationship'] : null,
            pivotAttributes: $pivotAttributes,
            displayColumns: $displayColumns,
            ownerTypes: $ownerTypes,
            label: is_string($config['label'] ?? null) ? $config['label'] : null,
            translationPrefix: is_string($config['translation_prefix'] ?? null) ? $config['translation_prefix'] : null,
            config: $config,
        );
    }

    /**
     * @return list<string>
     */
    private static function listOfStrings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if (array_is_list($value)) {
            return array_values(array_map(strval(...), $value));
        }

        return array_keys($value);
    }

    /**
     * @param  array<class-string|string, string|array{label?: string, title_attribute?: string|null}>  $raw
     * @return array<class-string, array{label: string, title_attribute?: string|null}>
     */
    private static function normalizeOwnerTypes(array $raw): array
    {
        $ownerTypes = [];

        foreach ($raw as $class => $definition) {
            if (! is_string($class) || $class === '') {
                continue;
            }

            if (is_string($definition)) {
                $ownerTypes[$class] = ['label' => $definition];

                continue;
            }

            if (! is_array($definition)) {
                continue;
            }

            $ownerTypes[$class] = [
                'label' => (string) ($definition['label'] ?? class_basename($class)),
                'title_attribute' => isset($definition['title_attribute']) && is_string($definition['title_attribute'])
                    ? $definition['title_attribute']
                    : null,
            ];
        }

        return $ownerTypes;
    }

    private function ensureResourceIsSet(): void
    {
        if ($this->currentResource === null) {
            throw RelationResourceNotSetException::make();
        }
    }
}
