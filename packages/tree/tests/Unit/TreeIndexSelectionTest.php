<?php

declare(strict_types=1);

use Moox\Tree\Support\TreeIndexSelection;
use Moox\Tree\Tests\Models\TreeNode;
use Moox\Tree\Tests\Support\CreatesTreeNodesTable;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class, CreatesTreeNodesTable::class);

beforeEach(function (): void {
    $this->createTreeNodesTable();
});

it('treats empty selection as visible', function (): void {
    expect(TreeIndexSelection::isVisibleInQuery(null, TreeNode::query()))->toBeTrue();
});

it('detects when selected record is in the current query', function (): void {
    $record = TreeNode::query()->create(['label' => 'Visible', 'sort_order' => 10]);
    TreeNode::query()->create(['label' => 'Hidden', 'sort_order' => 20]);

    $query = TreeNode::query()->where('label', 'Visible');

    expect(TreeIndexSelection::isVisibleInQuery((int) $record->getKey(), $query))->toBeTrue();
});

it('detects when selected record is outside the current query', function (): void {
    TreeNode::query()->create(['label' => 'Visible', 'sort_order' => 10]);
    $hidden = TreeNode::query()->create(['label' => 'Hidden', 'sort_order' => 20]);

    $query = TreeNode::query()->where('label', 'Visible');

    expect(TreeIndexSelection::isVisibleInQuery((int) $hidden->getKey(), $query))->toBeFalse();
});
