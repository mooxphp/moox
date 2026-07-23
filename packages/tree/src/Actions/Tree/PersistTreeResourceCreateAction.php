<?php

declare(strict_types=1);

namespace Moox\Tree\Actions\Tree;

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\TreeIndexResourcePages;
use Moox\Tree\Support\TreeResourcePageExecutor;

final class PersistTreeResourceCreateAction
{
    public function __construct(
        private readonly TreeResourcePageExecutor $executor,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        TreeIndexConfiguration $configuration,
        array $data,
        object $host,
        ?int $parentId = null,
    ): Model {
        $createPageClass = $configuration->getInspectorCreatePageClass()
            ?? TreeIndexResourcePages::resolveCreatePageClass($configuration);

        if ($createPageClass === null) {
            throw new \LogicException('Tree index configuration has no create page on the source resource.');
        }

        $page = $this->executor->makePage($createPageClass, $host);

        $data = $this->executor->mutateFormDataBeforeCreate($page, $data);

        $parentColumn = $configuration->getParentColumn();
        $resolvedParentId = $data[$parentColumn] ?? $parentId;

        if ($parentId !== null && ! array_key_exists($parentColumn, $data)) {
            $data[$parentColumn] = $parentId;
        }

        $data = app(AssignTreeNodePositionAction::class, ['configuration' => $configuration])
            ->applyAdjacencySortToFormData($data, $resolvedParentId);

        $record = $this->executor->handleRecordCreation($page, $data);

        $this->executor->mountPageRecord($page, $record);

        app(AssignTreeNodePositionAction::class, ['configuration' => $configuration])
            ->positionNestedSetAfterCreate($record, $parentId);

        $this->executor->callAfterCreate($page);

        return $record;
    }
}
