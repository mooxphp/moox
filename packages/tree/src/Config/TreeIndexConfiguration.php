<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Config;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class TreeIndexConfiguration
{
    /** @var (\Closure(Builder): Builder)|null */
    private readonly ?\Closure $modifyQuery;

    /**
     * @param  class-string|null  $inspectorPageClass
     */
    private function __construct(
        private readonly string $modelClass,
        private readonly string $parentColumn,
        private readonly string $sortColumn,
        private readonly string $labelColumn,
        private readonly bool $labelColumnQueryable,
        private readonly bool $nestedSet,
        private readonly bool $reorderable,
        private readonly ?string $inspectorPageClass,
        private readonly ?string $authorizationAbility,
        private readonly string $treeHeading,
        private readonly string $treeSubheading,
        private readonly string $inspectorHeading,
        private readonly string $createRootLabel,
        private readonly string $createChildLabel,
        private readonly string $saveLabel,
        private readonly string $newRecordLabel,
        private readonly string $deleteConfirmMessage,
        ?\Closure $modifyQuery = null,
    ) {
        $this->modifyQuery = $modifyQuery;
    }

    public static function make(string $modelClass): self
    {
        return new self(
            modelClass: $modelClass,
            parentColumn: 'parent_id',
            sortColumn: 'sort_order',
            labelColumn: 'label',
            labelColumnQueryable: true,
            nestedSet: false,
            reorderable: true,
            inspectorPageClass: null,
            authorizationAbility: null,
            treeHeading: 'Struktur',
            treeSubheading: 'Baum',
            inspectorHeading: 'Einstellungen',
            createRootLabel: 'Neuer Eintrag',
            createChildLabel: 'Untereintrag hinzufügen',
            saveLabel: 'Speichern',
            newRecordLabel: 'Neuer Eintrag',
            deleteConfirmMessage: 'Diesen Eintrag inklusive Untereinträge löschen?',
        );
    }

    public function parentColumn(string $parentColumn): self
    {
        return $this->cloneWith(parentColumn: $parentColumn);
    }

    public function sortColumn(string $sortColumn): self
    {
        return $this->cloneWith(sortColumn: $sortColumn);
    }

    public function labelColumn(string $labelColumn): self
    {
        return $this->cloneWith(labelColumn: $labelColumn);
    }

    public function labelColumnQueryable(bool $labelColumnQueryable = true): self
    {
        return $this->cloneWith(labelColumnQueryable: $labelColumnQueryable);
    }

    public function nestedSet(bool $nestedSet = true): self
    {
        return $this->cloneWith(nestedSet: $nestedSet);
    }

    public function authorizationAbility(?string $authorizationAbility): self
    {
        return $this->cloneWith(authorizationAbility: $authorizationAbility);
    }

    public function reorderable(bool $reorderable = true): self
    {
        return $this->cloneWith(reorderable: $reorderable);
    }

    /**
     * @param  class-string  $inspectorPageClass
     */
    public function inspectorPage(string $inspectorPageClass): self
    {
        return $this->cloneWith(inspectorPageClass: $inspectorPageClass);
    }

    /**
     * @return class-string|null
     */
    public function getInspectorPageClass(): ?string
    {
        return $this->inspectorPageClass;
    }

    public function usesResourceInspector(): bool
    {
        return $this->inspectorPageClass !== null;
    }

    /**
     * @param  \Closure(Builder): Builder  $modifyQuery
     */
    public function modifyQuery(\Closure $modifyQuery): self
    {
        return $this->cloneWith(modifyQuery: $modifyQuery);
    }

    public function labels(
        ?string $treeHeading = null,
        ?string $treeSubheading = null,
        ?string $inspectorHeading = null,
        ?string $createRootLabel = null,
        ?string $createChildLabel = null,
        ?string $saveLabel = null,
        ?string $newRecordLabel = null,
        ?string $deleteConfirmMessage = null,
    ): self {
        return $this->cloneWith(
            treeHeading: $treeHeading ?? $this->treeHeading,
            treeSubheading: $treeSubheading ?? $this->treeSubheading,
            inspectorHeading: $inspectorHeading ?? $this->inspectorHeading,
            createRootLabel: $createRootLabel ?? $this->createRootLabel,
            createChildLabel: $createChildLabel ?? $this->createChildLabel,
            saveLabel: $saveLabel ?? $this->saveLabel,
            newRecordLabel: $newRecordLabel ?? $this->newRecordLabel,
            deleteConfirmMessage: $deleteConfirmMessage ?? $this->deleteConfirmMessage,
        );
    }

    public function modelClass(): string
    {
        return $this->modelClass;
    }

    public function getParentColumn(): string
    {
        return $this->parentColumn;
    }

    public function getSortColumn(): string
    {
        return $this->sortColumn;
    }

    public function getLabelColumn(): string
    {
        return $this->labelColumn;
    }

    public function isLabelColumnQueryable(): bool
    {
        return $this->labelColumnQueryable;
    }

    public function usesNestedSet(): bool
    {
        return $this->nestedSet;
    }

    public function isReorderable(): bool
    {
        return $this->reorderable;
    }

    public function applyQuery(Builder $query): Builder
    {
        if ($this->modifyQuery === null) {
            return $query;
        }

        return ($this->modifyQuery)($query);
    }

    public function newQuery(): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->modelClass;

        return $this->applyQuery($modelClass::query());
    }

    public function siblingsQuery(int|string|null $parentId): Builder
    {
        $parentColumn = $this->getParentColumn();
        $parentId = $parentId === null ? null : (int) $parentId;

        return $this->newQuery()
            ->when(
                $parentId === null,
                fn (Builder $query): Builder => $query->whereNull($parentColumn),
                fn (Builder $query): Builder => $query->where($parentColumn, $parentId),
            );
    }

    public function getAuthorizationAbility(): ?string
    {
        return $this->authorizationAbility;
    }

    public function treeHeading(): string
    {
        return $this->treeHeading;
    }

    public function treeSubheading(): string
    {
        return $this->treeSubheading;
    }

    public function inspectorHeading(): string
    {
        return $this->inspectorHeading;
    }

    public function createRootLabel(): string
    {
        return $this->createRootLabel;
    }

    public function createChildLabel(): string
    {
        return $this->createChildLabel;
    }

    public function saveLabel(): string
    {
        return $this->saveLabel;
    }

    public function newRecordLabel(): string
    {
        return $this->newRecordLabel;
    }

    public function deleteConfirmMessage(): string
    {
        return $this->deleteConfirmMessage;
    }

    /**
     * @return array<int, string>
     */
    public function treeSelectColumns(): array
    {
        $columns = ['id', $this->getParentColumn(), $this->getSortColumn()];

        if ($this->usesNestedSet()) {
            $columns[] = '_rgt';
        }

        if ($this->isLabelColumnQueryable()) {
            $columns[] = $this->getLabelColumn();
        }

        return array_values(array_unique($columns));
    }

    public function applyTreeOrdering(Builder $query): Builder
    {
        $query->orderBy($this->getSortColumn());

        if ($this->isLabelColumnQueryable()) {
            $query->orderBy($this->getLabelColumn());
        }

        return $query;
    }

    private function cloneWith(
        ?string $modelClass = null,
        ?string $parentColumn = null,
        ?string $sortColumn = null,
        ?string $labelColumn = null,
        ?bool $labelColumnQueryable = null,
        ?bool $nestedSet = null,
        ?bool $reorderable = null,
        ?string $inspectorPageClass = null,
        ?string $authorizationAbility = null,
        ?string $treeHeading = null,
        ?string $treeSubheading = null,
        ?string $inspectorHeading = null,
        ?string $createRootLabel = null,
        ?string $createChildLabel = null,
        ?string $saveLabel = null,
        ?string $newRecordLabel = null,
        ?string $deleteConfirmMessage = null,
        ?\Closure $modifyQuery = null,
    ): self {
        return new self(
            modelClass: $modelClass ?? $this->modelClass,
            parentColumn: $parentColumn ?? $this->parentColumn,
            sortColumn: $sortColumn ?? $this->sortColumn,
            labelColumn: $labelColumn ?? $this->labelColumn,
            labelColumnQueryable: $labelColumnQueryable ?? $this->labelColumnQueryable,
            nestedSet: $nestedSet ?? $this->nestedSet,
            reorderable: $reorderable ?? $this->reorderable,
            inspectorPageClass: $inspectorPageClass ?? $this->inspectorPageClass,
            authorizationAbility: $authorizationAbility ?? $this->authorizationAbility,
            treeHeading: $treeHeading ?? $this->treeHeading,
            treeSubheading: $treeSubheading ?? $this->treeSubheading,
            inspectorHeading: $inspectorHeading ?? $this->inspectorHeading,
            createRootLabel: $createRootLabel ?? $this->createRootLabel,
            createChildLabel: $createChildLabel ?? $this->createChildLabel,
            saveLabel: $saveLabel ?? $this->saveLabel,
            newRecordLabel: $newRecordLabel ?? $this->newRecordLabel,
            deleteConfirmMessage: $deleteConfirmMessage ?? $this->deleteConfirmMessage,
            modifyQuery: $modifyQuery ?? $this->modifyQuery,
        );
    }
}
