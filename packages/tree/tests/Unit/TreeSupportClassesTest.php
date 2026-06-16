<?php

declare(strict_types=1);

use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Support\NestedSetGuard;
use Moox\Tree\Support\TreeNodeLabelResolver;
use Moox\Tree\Tests\Models\TreeNode;
use Moox\Tree\Tests\Support\CreatesTreeNodesTable;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class, CreatesTreeNodesTable::class);

beforeEach(function (): void {
    $this->createTreeNodesTable();
});

it('resolves label from configured column', function (): void {
    $record = new TreeNode(['label' => 'Root']);
    $configuration = TreeIndexConfiguration::make(TreeNode::class);

    expect(TreeNodeLabelResolver::resolve($record, $configuration))->toBe('Root');
});

it('falls back to display_title when label column is empty', function (): void {
    $record = new TreeNode(['label' => '']);
    $record->setAttribute('display_title', 'Fallback Title');
    $configuration = TreeIndexConfiguration::make(TreeNode::class);

    expect(TreeNodeLabelResolver::resolve($record, $configuration))->toBe('Fallback Title');
});

it('returns empty string when no label sources exist', function (): void {
    $record = new TreeNode(['label' => '']);
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->labelFallbackColumn(null);

    expect(TreeNodeLabelResolver::resolve($record, $configuration))->toBe('');
});

it('asserts nested set capability on model class', function (): void {
    NestedSetGuard::assertCapable(TreeNode::class);
})->throws(InvalidArgumentException::class);

it('computes next sort order for siblings', function (): void {
    TreeNode::query()->create(['label' => 'A', 'sort_order' => 10]);
    TreeNode::query()->create(['label' => 'B', 'sort_order' => 30]);

    $configuration = TreeIndexConfiguration::make(TreeNode::class);

    expect($configuration->nextSortOrder(null))->toBe(40);
});

it('scopes siblingsExcept to ordered siblings without excluded id', function (): void {
    $parent = TreeNode::query()->create(['label' => 'Parent', 'sort_order' => 0]);
    $first = TreeNode::query()->create(['label' => 'First', 'parent_id' => $parent->id, 'sort_order' => 10]);
    TreeNode::query()->create(['label' => 'Second', 'parent_id' => $parent->id, 'sort_order' => 20]);

    $configuration = TreeIndexConfiguration::make(TreeNode::class);
    $ids = $configuration->siblingsExcept($parent->id, $first->id)->pluck('id')->all();

    expect($ids)->toHaveCount(1);
});
