<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\TreeStructure;
use Moox\Tree\Tests\Models\TreeNode;

it('builds a nested tree from flat records', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)->labelColumn('label');
    $structure = new TreeStructure($configuration);

    $parent = new TreeNode(['parent_id' => null, 'label' => 'Root', 'sort_order' => 0]);
    $parent->setAttribute('id', 1);
    $child = new TreeNode(['parent_id' => 1, 'label' => 'Child', 'sort_order' => 10]);
    $child->setAttribute('id', 2);

    $tree = $structure->buildTree($structure->groupByParent(collect([$parent, $child])));

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['label'])->toBe('Root')
        ->and($tree[0]['children'])->toHaveCount(1);
});

it('builds a nested set tree from _lft and _rgt values', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->labelColumn('label')
        ->nestedSet()
        ->sortColumn('_lft');
    $structure = new TreeStructure($configuration);

    $root = new TreeNode(['parent_id' => null, 'label' => 'Root']);
    $root->forceFill(['id' => 1, '_lft' => 1, '_rgt' => 4]);
    $child = new TreeNode(['parent_id' => 1, 'label' => 'Child']);
    $child->forceFill(['id' => 2, '_lft' => 2, '_rgt' => 3]);

    $tree = $structure->buildNestedSetTree(collect([$root, $child]));

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['label'])->toBe('Root')
        ->and($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label'])->toBe('Child');
});

it('collects descendant ids', function (): void {
    $structure = new TreeStructure(TreeIndexConfiguration::make(TreeNode::class));

    $records = collect([
        tap(new TreeNode, fn (Model $model): Model => $model->forceFill(['id' => 1, 'parent_id' => null])),
        tap(new TreeNode, fn (Model $model): Model => $model->forceFill(['id' => 2, 'parent_id' => 1])),
        tap(new TreeNode, fn (Model $model): Model => $model->forceFill(['id' => 3, 'parent_id' => 2])),
    ]);

    $grouped = $structure->groupByParent($records);

    expect($structure->descendantIds($grouped, 1))->toBe([2, 3]);
});
