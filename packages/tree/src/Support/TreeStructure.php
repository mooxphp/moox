<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Tree\Config\TreeIndexConfiguration;

final class TreeStructure
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    /**
     * @param  Collection<int, Model>  $records
     * @return Collection<string, Collection<int, Model>>
     */
    public function groupByParent(Collection $records): Collection
    {
        return $records->groupBy(fn (Model $record): string => $this->parentKey($this->parentId($record)));
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array<int, array<string, mixed>>
     */
    public function buildNestedSetTree(Collection $records): array
    {
        if ($records->isEmpty()) {
            return [];
        }

        $sortColumn = $this->configuration->getSortColumn();
        $ordered = $records->sortBy(fn (Model $record): int => (int) $record->getAttribute($sortColumn))->values();

        /** @var list<array<string, mixed>> $stack */
        $stack = [];

        /** @var list<array<string, mixed>> $roots */
        $roots = [];

        foreach ($ordered as $record) {
            $node = $this->mapRecordToNode($record);
            $node['_lft'] = (int) $record->getAttribute('_lft');
            $node['_rgt'] = (int) $record->getAttribute('_rgt');

            while ($stack !== [] && $stack[array_key_last($stack)]['_rgt'] < $node['_lft']) {
                array_pop($stack);
            }

            if ($stack === []) {
                $roots[] = &$node;
            } else {
                $stack[array_key_last($stack)]['children'][] = &$node;
            }

            $stack[] = &$node;
            unset($node);
        }

        return $this->stripNestedSetMeta($roots);
    }

    /**
     * @param  Collection<string, Collection<int, Model>>  $recordsByParent
     * @return array<int, array<string, mixed>>
     */
    public function buildTree(Collection $recordsByParent): array
    {
        $allRecords = $recordsByParent->flatten()->unique(
            fn (Model $record): int => (int) $record->getKey(),
        );

        if ($allRecords->isEmpty()) {
            return [];
        }

        /** @var array<int, true> $knownIds */
        $knownIds = $allRecords
            ->mapWithKeys(fn (Model $record): array => [(int) $record->getKey() => true])
            ->all();

        $roots = $allRecords->filter(function (Model $record) use ($knownIds): bool {
            $parentId = $this->parentId($record);

            return $parentId === null || ! isset($knownIds[$parentId]);
        });

        return $this->sortRecords($roots)
            ->map(fn (Model $record): array => $this->mapRecordToNode($record, $recordsByParent))
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function ancestorIds(int $recordId): array
    {
        $parentColumn = $this->configuration->getParentColumn();

        /** @var array<int|string, int|string|null> $parentMap */
        $parentMap = $this->configuration->newQuery()->pluck($parentColumn, 'id')->all();

        $ids = [];
        $parentId = $parentMap[$recordId] ?? null;

        while ($parentId !== null && $parentId !== '') {
            $parentId = (int) $parentId;
            $ids[] = $parentId;
            $parentId = $parentMap[$parentId] ?? null;
        }

        return $ids;
    }

    /**
     * @param  Collection<string, Collection<int, Model>>  $recordsByParent
     * @return array<int, int>
     */
    public function descendantIds(Collection $recordsByParent, int $recordId): array
    {
        return $recordsByParent
            ->get($this->parentKey($recordId), collect())
            ->flatMap(fn (Model $record): array => [
                (int) $record->getKey(),
                ...$this->descendantIds($recordsByParent, (int) $record->getKey()),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id: int, children: array<int, mixed>}>  $nodes
     * @return array<int, int>
     */
    public function branchIdsWithChildren(array $nodes): array
    {
        $ids = [];

        foreach ($nodes as $node) {
            if ($node['children'] !== []) {
                $ids[] = $node['id'];
                $ids = [...$ids, ...$this->branchIdsWithChildren($node['children'])];
            }
        }

        return $ids;
    }

    private function parentKey(?int $parentId): string
    {
        return $parentId === null ? 'root' : (string) $parentId;
    }

    public function parentId(Model $record): ?int
    {
        $parentId = $record->getAttribute($this->configuration->getParentColumn());

        if ($parentId === null || $parentId === '') {
            return null;
        }

        return (int) $parentId;
    }

    /**
     * @param  Collection<string, Collection<int, Model>>  $recordsByParent
     * @return array<int, array<string, mixed>>
     */
    private function buildBranch(Collection $recordsByParent, int $parentId): array
    {
        return $this->sortRecords($recordsByParent->get($this->parentKey($parentId), collect()))
            ->map(fn (Model $record): array => $this->mapRecordToNode($record, $recordsByParent))
            ->all();
    }

    /**
     * @param  Collection<string, Collection<int, Model>>|null  $recordsByParent
     * @return array<string, mixed>
     */
    private function mapRecordToNode(Model $record, ?Collection $recordsByParent = null): array
    {
        $parentColumn = $this->configuration->getParentColumn();

        return [
            'id' => (int) $record->getKey(),
            $parentColumn => $this->parentId($record),
            'label' => TreeNodeLabelResolver::resolve($record, $this->configuration),
            'children' => $recordsByParent === null
                ? []
                : $this->buildBranch($recordsByParent, (int) $record->getKey()),
        ];
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return Collection<int, Model>
     */
    private function sortRecords(Collection $records): Collection
    {
        $sortColumn = $this->configuration->getSortColumn();

        return $records
            ->sortBy(fn (Model $record): int => (int) $record->getAttribute($sortColumn))
            ->values();
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function stripNestedSetMeta(array $nodes): array
    {
        return array_map(function (array $node): array {
            unset($node['_lft'], $node['_rgt']);

            $node['children'] = $this->stripNestedSetMeta($node['children']);

            return $node;
        }, $nodes);
    }
}
