<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\NestedSetGuard;

final class CreateNestedSetTreeNodeAction
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function handle(?int $parentId = null): Model
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->configuration->modelClass();

        NestedSetGuard::assertCapable($modelClass);

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
