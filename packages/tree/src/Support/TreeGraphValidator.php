<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Exceptions\InvalidTreeParentException;

final class TreeGraphValidator
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function validateParentAssignment(Model $record, int|string|null $newParentId): void
    {
        if ($newParentId === null || $newParentId === '') {
            return;
        }

        $newParentId = (int) $newParentId;
        $recordId = (int) $record->getKey();

        if ($newParentId === $recordId) {
            throw InvalidTreeParentException::selfParent();
        }

        if ($this->isDescendantOf($newParentId, $recordId)) {
            throw InvalidTreeParentException::descendantAsParent();
        }
    }

    public function validateMove(Model $record, int|string|null $newParentId): void
    {
        if ($newParentId === null || $newParentId === '') {
            return;
        }

        $newParentId = (int) $newParentId;
        $recordId = (int) $record->getKey();

        if ($newParentId === $recordId || $this->isDescendantOf($newParentId, $recordId)) {
            throw InvalidTreeParentException::moveBlocked();
        }
    }

    private function isDescendantOf(int $candidateRecordId, int $parentRecordId): bool
    {
        $structure = new TreeStructure($this->configuration);
        $parentColumn = $this->configuration->getParentColumn();
        $records = $this->configuration->newQuery()->get(['id', $parentColumn]);

        return in_array(
            $candidateRecordId,
            $structure->descendantIds($structure->groupByParent($records), $parentRecordId),
            true,
        );
    }
}
