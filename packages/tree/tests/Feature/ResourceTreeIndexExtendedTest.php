<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
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

    TreeIndexConfigurationRegistry::register(
        'reorderable-tree',
        TreeIndexConfiguration::make(TreeNode::class),
    );
});

it('reorders siblings when reordering is enabled', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $first = TreeNode::query()->create(['label' => 'First', 'sort_order' => 10]);
    $second = TreeNode::query()->create(['label' => 'Second', 'sort_order' => 20]);

    Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'reorderable-tree',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('moveTreeNode', $second->id, 0, null)
        ->assertHasNoErrors();

    expect(TreeNode::query()->orderBy('sort_order')->pluck('label')->all())->toBe(['Second', 'First']);
});

it('saves the selected record', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $node = TreeNode::query()->create(['label' => 'Original', 'sort_order' => 10]);

    Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'reorderable-tree',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('selectRecord', $node->id)
        ->set('form.label', 'Updated')
        ->call('saveSelectedRecord')
        ->assertHasNoErrors();

    expect(TreeNode::query()->find($node->id)?->label)->toBe('Updated');
});

it('deletes the selected record', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    TreeNode::query()->create(['label' => 'Keep', 'sort_order' => 0]);
    $remove = TreeNode::query()->create(['label' => 'Remove', 'sort_order' => 10]);

    Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'reorderable-tree',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('selectRecord', $remove->id)
        ->call('deleteSelectedRecord')
        ->assertHasNoErrors();

    expect(TreeNode::query()->whereKey($remove->id)->exists())->toBeFalse();
});

it('blocks move under a descendant', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $root = TreeNode::query()->create(['label' => 'Root', 'sort_order' => 0]);
    $child = TreeNode::query()->create(['label' => 'Child', 'parent_id' => $root->id, 'sort_order' => 10]);

    Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'reorderable-tree',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('moveTreeNode', $root->id, 0, $child->id)
        ->assertHasNoErrors();

    expect(TreeNode::query()->find($root->id)?->parent_id)->toBeNull();
});

it('requires authorization when enabled', function (): void {
    config(['filament-tree-index.authorization.enabled' => true]);

    Gate::define('updateTree', fn (): bool => false);

    $configuration = TreeIndexConfiguration::make(TreeNode::class)->authorizationAbility('updateTree');
    $authorizer = new \Moox\Tree\Support\TreeIndexAuthorizer($configuration);

    expect(fn () => $authorizer->authorize())
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});
