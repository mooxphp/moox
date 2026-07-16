<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Collection;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;

/**
 * Converts legacy flat tab markers (fields listed after a tab until the next tab)
 * into tab containers with nested children.
 */
class TabStructureMigrator
{
    public function migrateGroup(FieldGroup $group): bool
    {
        $roots = $group->fields()
            ->whereNull('parent_field_id')
            ->orderBy('sort')
            ->get();

        if ($roots->isEmpty()) {
            return false;
        }

        $tabFields = $roots->where('type', 'tab');

        if ($tabFields->isEmpty()) {
            return false;
        }

        $needsMigration = $tabFields->contains(
            fn (Field $tab): bool => $tab->children()->count() === 0
                && $this->hasFollowingRootFields($roots, $tab),
        );

        if (! $needsMigration) {
            return false;
        }

        $pendingChildren = collect();
        $currentTab = null;

        foreach ($roots as $field) {
            if ($field->type === 'tab') {
                if ($currentTab instanceof Field && $pendingChildren->isNotEmpty()) {
                    $this->assignChildren($currentTab, $pendingChildren);
                }

                $currentTab = $field;
                $pendingChildren = collect();

                continue;
            }

            if ($currentTab instanceof Field) {
                $pendingChildren->push($field);
            }
        }

        if ($currentTab instanceof Field && $pendingChildren->isNotEmpty()) {
            $this->assignChildren($currentTab, $pendingChildren);
        }

        return true;
    }

    /**
     * @param  Collection<int, Field>  $roots
     */
    protected function hasFollowingRootFields(Collection $roots, Field $tab): bool
    {
        $afterTab = false;

        foreach ($roots as $field) {
            if ($field->is($tab)) {
                $afterTab = true;

                continue;
            }

            if ($afterTab && $field->type !== 'tab') {
                return true;
            }

            if ($afterTab && $field->type === 'tab') {
                return false;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, Field>  $children
     */
    protected function assignChildren(Field $tab, Collection $children): void
    {
        foreach ($children->values() as $sort => $child) {
            $child->update([
                'parent_field_id' => $tab->getKey(),
                'sort' => $sort,
            ]);
        }
    }
}
