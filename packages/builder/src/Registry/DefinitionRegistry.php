<?php

declare(strict_types=1);

namespace Moox\Builder\Registry;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Moox\Builder\Compiler\LocationMatcher;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Data\LocationContext;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Support\FieldRelationTree;

class DefinitionRegistry
{
    public const CACHE_KEY = 'builder.definitions';

    public function __construct(
        protected LocationMatcher $locationMatcher,
    ) {}

    /**
     * @return Collection<int, FieldGroupDefinition>
     */
    public function fieldGroupsFor(LocationContext $context): Collection
    {
        return $this->allActiveGroups()
            ->filter(fn (FieldGroupDefinition $group): bool => $this->locationMatcher->matches(
                $group->locationRules,
                $context,
            ))
            ->values();
    }

    /**
     * @return Collection<int, FieldGroupDefinition>
     */
    public function allActiveGroups(): Collection
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (! is_array($cached)) {
            if ($cached !== null) {
                Cache::forget(self::CACHE_KEY);
            }

            $cached = $this->loadActiveGroupsAsArrays();
            Cache::forever(self::CACHE_KEY, $cached);
        }

        return collect($cached)
            ->map(fn (array $data): FieldGroupDefinition => FieldGroupDefinition::fromArray($data))
            ->values();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function loadActiveGroupsAsArrays(): array
    {
        return FieldGroup::query()
            ->active()
            ->with(FieldRelationTree::eagerLoadForDefinition())
            ->orderBy('sort')
            ->get()
            ->map(fn (FieldGroup $group): array => FieldGroupDefinition::fromModel($group)->toArray())
            ->all();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
