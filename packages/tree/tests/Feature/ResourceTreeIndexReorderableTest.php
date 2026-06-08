<?php

declare(strict_types=1);

use Livewire\Livewire;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Livewire\ResourceTreeIndex;
use Moox\Tree\Tests\Models\TreeNode;
use Moox\Tree\Tests\Support\CreatesTreeNodesTable;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class, CreatesTreeNodesTable::class);

beforeEach(function (): void {
    $this->createTreeNodesTable();

    $root = TreeNode::query()->create(['label' => 'Root', 'sort_order' => 0]);
    TreeNode::query()->create(['label' => 'Child', 'parent_id' => $root->id, 'sort_order' => 10]);

    TreeIndexConfigurationRegistry::register(
        'non-reorderable',
        TreeIndexConfiguration::make(TreeNode::class)->reorderable(false),
    );
});

it('ignores moveTreeNode when reordering is disabled', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $child = TreeNode::query()->where('label', 'Child')->firstOrFail();

    Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'non-reorderable',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('moveTreeNode', $child->id, 0, null)
        ->assertHasNoErrors();

    expect(TreeNode::query()->findOrFail($child->id)->parent_id)->toBe(TreeNode::query()->where('label', 'Root')->value('id'));
});
