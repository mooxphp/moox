<?php

declare(strict_types=1);

use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Exceptions\InvalidTreeParentException;
use Moox\Tree\Support\TreeGraphValidator;
use Moox\Tree\Support\TreeStructure;
use Moox\Tree\Tests\Models\TreeNode;
use Moox\Tree\Tests\Support\CreatesTreeNodesTable;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class, CreatesTreeNodesTable::class);

beforeEach(function (): void {
    $this->createTreeNodesTable();
});

it('detects self-parent assignments', function (): void {
    $root = TreeNode::query()->create(['label' => 'Root', 'sort_order' => 0]);
    $configuration = TreeIndexConfiguration::make(TreeNode::class);
    $validator = new TreeGraphValidator($configuration);

    expect(fn () => $validator->validateParentAssignment($root, $root->id))
        ->toThrow(InvalidTreeParentException::class);
});

it('detects descendant as parent', function (): void {
    $root = TreeNode::query()->create(['label' => 'Root', 'sort_order' => 0]);
    $child = TreeNode::query()->create(['label' => 'Child', 'parent_id' => $root->id, 'sort_order' => 10]);
    $configuration = TreeIndexConfiguration::make(TreeNode::class);
    $validator = new TreeGraphValidator($configuration);

    expect(fn () => $validator->validateParentAssignment($root, $child->id))
        ->toThrow(InvalidTreeParentException::class);
});

it('resolves ancestor ids from parent map', function (): void {
    $root = TreeNode::query()->create(['label' => 'Root', 'sort_order' => 0]);
    $child = TreeNode::query()->create(['label' => 'Child', 'parent_id' => $root->id, 'sort_order' => 10]);
    $grandchild = TreeNode::query()->create(['label' => 'Grandchild', 'parent_id' => $child->id, 'sort_order' => 20]);

    $structure = new TreeStructure(TreeIndexConfiguration::make(TreeNode::class));

    expect($structure->ancestorIds((int) $grandchild->id))->toBe([(int) $child->id, (int) $root->id]);
});
