<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\TreeGraphValidator;

final class MoveTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration)
    {
    }

    public function handle(Model $record, ?int $newParentId, int $position): void
    {
        app(TreeGraphValidator::class, ['configuration' => $this->configuration])
            ->validateMove($record, $newParentId);

        DB::transaction(function () use ($record, $newParentId, $position): void {
            if ($this->configuration->usesNestedSet()) {
                app(MoveNestedSetTreeNodeAction::class, ['configuration' => $this->configuration])
                    ->handle($record, $newParentId, $position);

                return;
            }

            $record->forceFill([
                $this->configuration->getParentColumn() => $newParentId,
            ])->save();

            $this->reorderSiblings($newParentId, $record, $position);
        });
    }

    private function reorderSiblings(?int $parentId, Model $movedRecord, int $position): void
    {
        $siblings = $this->configuration
            ->siblingsExcept($parentId, $movedRecord->getKey())
            ->get();

        $position = max(0, min($position, $siblings->count()));
        $siblings->splice($position, 0, [$movedRecord]);

        $siblings
            ->values()
            ->each(function (Model $record, int $index) use ($parentId): void {
                $record->forceFill([
                    $this->configuration->getParentColumn() => $parentId,
                    $this->configuration->getSortColumn() => $index * 10,
                ])->save();
            });
    }
}
