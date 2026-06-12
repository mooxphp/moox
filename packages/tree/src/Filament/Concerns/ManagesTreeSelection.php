<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Support\TreeNodeLabelResolver;

trait ManagesTreeSelection
{
    public bool $isCreatingInspector = false;

    public ?int $creatingParentId = null;

    public function selectRecord(int $recordId): void
    {
        $this->authorizeTreeIndex();
        $this->isCreatingInspector = false;
        $this->creatingParentId = null;
        $this->treeSelectedId = $recordId;
        $this->loadInspectorOrStubForm();
    }

    protected function getSelectedRecord(): ?Model
    {
        if ($this->treeSelectedId === null) {
            return null;
        }

        return $this->query()->find($this->treeSelectedId);
    }

    /**
     * @return array<int, string>
     */
    protected function getParentOptions(): array
    {
        $configuration = $this->configuration();
        $labelColumn = $configuration->getLabelColumn();
        $excludedIds = $this->treeSelectedId === null
            ? []
            : [$this->treeSelectedId, ...$this->getDescendantIds($this->treeSelectedId)];

        $columns = ['id'];

        if ($configuration->isLabelColumnQueryable()) {
            $columns[] = $labelColumn;
        }

        return $configuration
            ->applyTreeOrdering($this->query())
            ->get($columns)
            ->reject(fn (Model $record): bool => in_array((int) $record->getKey(), $excludedIds, true))
            ->mapWithKeys(fn (Model $record): array => [
                (int) $record->getKey() => TreeNodeLabelResolver::resolve($record, $configuration),
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    protected function getDescendantIds(int $recordId): array
    {
        $structure = $this->treeStructure();
        $parentColumn = $this->configuration()->getParentColumn();
        $records = $this->query()->get(['id', $parentColumn]);

        return $structure->descendantIds($structure->groupByParent($records), $recordId);
    }
}
