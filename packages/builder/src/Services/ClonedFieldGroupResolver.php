<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\DefinitionRegistry;

/**
 * Resolves cloned field group definitions at runtime (reference semantics).
 */
final class ClonedFieldGroupResolver
{
    /** @var array<string, Collection<int, FieldDefinition>> */
    protected array $cache = [];

    public function __construct(
        protected DefinitionRegistry $definitionRegistry,
    ) {}

    /**
     * @return Collection<int, FieldDefinition>
     */
    public function compoundChildren(FieldDefinition $field): Collection
    {
        if ($field->type === 'clone') {
            return $this->resolveChildren($field);
        }

        return $field->children;
    }

    /**
     * @return Collection<int, FieldDefinition>
     */
    public function resolveChildren(FieldDefinition $field): Collection
    {
        $slug = $this->targetSlug($field);

        if ($slug === '') {
            return collect();
        }

        return $this->resolveBySlug($slug);
    }

    /**
     * @return Collection<int, FieldDefinition>
     */
    public function resolveBySlug(string $slug): Collection
    {
        if (array_key_exists($slug, $this->cache)) {
            return $this->cache[$slug];
        }

        $group = $this->definitionRegistry->allActiveGroups()->firstWhere('slug', $slug);

        $this->cache[$slug] = $group?->fields ?? collect();

        return $this->cache[$slug];
    }

    public function targetSlug(FieldDefinition $field): string
    {
        return trim((string) ($field->config['field_group_slug'] ?? ''));
    }

    public function hasTarget(FieldDefinition $field): bool
    {
        return $this->targetSlug($field) !== ''
            && $this->resolveBySlug($this->targetSlug($field))->isNotEmpty();
    }

    /**
     * @return array<string, string>
     */
    public function optionsForSelect(?string $excludeSlug = null): array
    {
        return FieldGroup::query()
            ->active()
            ->when(filled($excludeSlug), fn ($query) => $query->where('slug', '!=', $excludeSlug))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (FieldGroup $group): array => [
                (string) $group->slug => (string) $group->name,
            ])
            ->all();
    }

    public function isActiveSlug(string $slug): bool
    {
        if ($slug === '') {
            return false;
        }

        return $this->definitionRegistry->allActiveGroups()->contains(
            fn ($group): bool => $group->slug === $slug,
        );
    }
}
