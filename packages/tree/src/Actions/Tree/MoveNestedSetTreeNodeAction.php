<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kalnoy\Nestedset\NodeTrait;
use Moox\Tree\Config\TreeIndexConfiguration;

final class MoveNestedSetTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function handle(Model $record, ?int $newParentId, int $position): void
    {
        $this->assertNestedSetCapable($record);

        $record = $record->fresh() ?? $record;

        $siblings = $this->configuration
            ->applyTreeOrdering(
                $this->configuration->siblingsQuery($newParentId)->whereKeyNot($record->getKey()),
            )
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

    private function assertNestedSetCapable(Model $record): void
    {
        if (! in_array(NodeTrait::class, class_uses_recursive($record), true)) {
            throw new InvalidArgumentException(
                'Nested set tree index requires Kalnoy\Nestedset\NodeTrait on the model.',
            );
        }
    }

    private function isRootNode(Model $record): bool
    {
        if (method_exists($record, 'isRoot')) {
            return $record->isRoot();
        }

        return $record->getAttribute($this->configuration->getParentColumn()) === null;
    }
}
