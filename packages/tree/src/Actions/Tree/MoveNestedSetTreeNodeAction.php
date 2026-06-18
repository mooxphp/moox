<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\NestedSetGuard;

final class MoveNestedSetTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function handle(Model $record, ?int $newParentId, int $position): void
    {
        NestedSetGuard::assertCapable($record);

        $record = $record->fresh() ?? $record;

        $siblings = $this->configuration
            ->siblingsExcept($newParentId, $record->getKey())
            ->get();

        $position = max(0, min($position, $siblings->count()));

        if ($siblings->isEmpty()) {
            $this->attachAsOnlyChild($record, $newParentId);

            return;
        }

        if ($position >= $siblings->count()) {
            $record->afterNode($siblings->last())->save();

            return;
        }

        $record->beforeNode($siblings->get($position))->save();
    }

    private function attachAsOnlyChild(Model $record, ?int $parentId): void
    {
        if ($parentId === null) {
            if ($record->exists && $this->isRootNode($record)) {
                return;
            }

            $record->makeRoot()->save();

            return;
        }

        $parent = $this->configuration->newQuery()->findOrFail($parentId);

        $record->appendToNode($parent)->save();
    }

    private function isRootNode(Model $record): bool
    {
        if (method_exists($record, 'isRoot')) {
            return $record->isRoot();
        }

        return $record->getAttribute($this->configuration->getParentColumn()) === null;
    }
}
