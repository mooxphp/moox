<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Actions\Tree;

use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kalnoy\Nestedset\NodeTrait;

final class CreateNestedSetTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function handle(?int $parentId = null): Model
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->configuration->modelClass();

        if (! in_array(NodeTrait::class, class_uses_recursive($modelClass), true)) {
            throw new InvalidArgumentException(
                'Nested set tree index requires Kalnoy\Nestedset\NodeTrait on the model.',
            );
        }

        $labelColumn = $this->configuration->getLabelColumn();
        $attributes = [];

        if ($this->configuration->isLabelColumnQueryable()) {
            $attributes[$labelColumn] = $this->configuration->newRecordLabel();
        }

        $record = new $modelClass($attributes);

        if (! $this->configuration->isLabelColumnQueryable()) {
            $record->setAttribute($labelColumn, $this->configuration->newRecordLabel());
        }

        if ($parentId === null) {
            $record->saveAsRoot();
        } else {
            $parent = $this->configuration->newQuery()->findOrFail($parentId);
            $record->appendToNode($parent)->save();
        }

        return $record->fresh() ?? $record;
    }
}
