<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\NestedSetGuard;

final class AssignTreeNodePositionAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function nextSortOrder(int|string|null $parentId): int
    {
        return $this->configuration->nextSortOrder($parentId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyAdjacencySortToFormData(array $data, int|string|null $parentId): array
    {
        if ($this->configuration->usesNestedSet()) {
            return $data;
        }

        $sortColumn = $this->configuration->getSortColumn();
        $data[$sortColumn] = $this->nextSortOrder($parentId);

        return $data;
    }

    public function positionNestedSetAfterCreate(Model $record, int|string|null $parentId): void
    {
        if (! $this->configuration->usesNestedSet()) {
            return;
        }

        NestedSetGuard::assertCapable($record);

        $parentColumn = $this->configuration->getParentColumn();

        if ($parentId === null) {
            $parentId = $record->getAttribute($parentColumn);
        }

        if ($parentId !== null && $parentId !== '') {
            $parent = $this->configuration->newQuery()->find((int) $parentId);

            if ($parent !== null) {
                $record->appendToNode($parent)->save();

                return;
            }
        }

        $record->saveAsRoot();
    }
}
