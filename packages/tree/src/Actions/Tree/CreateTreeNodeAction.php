<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Actions\Tree;

use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CreateTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function handle(?int $parentId = null): Model
    {
        return DB::transaction(fn (): Model => $this->createRecord($parentId));
    }

    private function createRecord(?int $parentId): Model
    {
        if ($this->configuration->usesNestedSet()) {
            return app(CreateNestedSetTreeNodeAction::class, ['configuration' => $this->configuration])
                ->handle($parentId);
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $this->configuration->modelClass();
        $parentColumn = $this->configuration->getParentColumn();
        $sortColumn = $this->configuration->getSortColumn();
        $labelColumn = $this->configuration->getLabelColumn();

        /** @var Model|null $parent */
        $parent = $parentId === null ? null : $this->configuration->newQuery()->find($parentId);
        $sortOrder = ((int) $this->configuration->siblingsQuery($parent?->getKey())->max($sortColumn)) + 10;

        $attributes = [
            $parentColumn => $parent?->getKey(),
            $sortColumn => $sortOrder,
        ];

        if ($this->configuration->isLabelColumnQueryable()) {
            $attributes[$labelColumn] = $this->configuration->newRecordLabel();
        }

        $record = $modelClass::query()->create($attributes);

        if (! $this->configuration->isLabelColumnQueryable()) {
            $record->setAttribute($labelColumn, $this->configuration->newRecordLabel());
            $record->save();
        }

        return $record;
    }
}
