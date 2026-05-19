<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Moox\Tree\Actions\Tree\MoveNestedSetTreeNodeAction;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Livewire\ResourceTreeIndex;
use Moox\Tree\Tests\Models\NestedSetTreeNode;

beforeEach(function (): void {
    Schema::dropIfExists('nested_set_tree_nodes');

    Schema::create('nested_set_tree_nodes', function (Blueprint $table): void {
        $table->id();
        $table->string('label');
        $table->nestedSet();
        $table->timestamps();
    });

    $rootA = NestedSetTreeNode::query()->create(['label' => 'Root A']);
    $rootB = NestedSetTreeNode::query()->create(['label' => 'Root B']);
    $child = new NestedSetTreeNode(['label' => 'Child']);
    $rootA->appendNode($child);

    TreeIndexConfigurationRegistry::register(
        'nested-set-reorderable',
        TreeIndexConfiguration::make(NestedSetTreeNode::class)
            ->nestedSet()
            ->sortColumn('_lft')
            ->reorderable(true),
    );
});

it('moves a nested set node to another parent and keeps _lft/_rgt consistent', function (): void {
    $configuration = TreeIndexConfiguration::make(NestedSetTreeNode::class)
        ->nestedSet()
        ->sortColumn('_lft');

    $child = NestedSetTreeNode::query()->where('label', 'Child')->firstOrFail();
    $rootB = NestedSetTreeNode::query()->where('label', 'Root B')->firstOrFail();

    app(MoveNestedSetTreeNodeAction::class, ['configuration' => $configuration])
        ->handle($child, (int) $rootB->getKey(), 0);

    $child->refresh();

    expect($child->parent_id)->toBe((int) $rootB->getKey())
        ->and($child->_rgt)->toBeGreaterThan($child->_lft);

    foreach (NestedSetTreeNode::query()->get() as $node) {
        expect($node->_rgt)->toBeGreaterThan($node->_lft);
    }
});

it('reorders nested set siblings via livewire moveTreeNode', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $rootA = NestedSetTreeNode::query()->where('label', 'Root A')->firstOrFail();
    $rootB = NestedSetTreeNode::query()->where('label', 'Root B')->firstOrFail();

    expect($rootA->_lft)->toBeLessThan($rootB->_lft);

    Livewire::test(ResourceTreeIndex::class, ['configurationKey' => 'nested-set-reorderable'])
        ->call('moveTreeNode', $rootB->id, 0, null)
        ->assertHasNoErrors();

    $rootA->refresh();
    $rootB->refresh();

    expect($rootB->_lft)->toBeLessThan($rootA->_lft);

    foreach (NestedSetTreeNode::query()->get() as $node) {
        expect($node->_rgt)->toBeGreaterThan($node->_lft);
    }
});

it('shows the inspector form when a tree node is selected', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $rootA = NestedSetTreeNode::query()->where('label', 'Root A')->firstOrFail();

    Livewire::test(ResourceTreeIndex::class, ['configurationKey' => 'nested-set-reorderable'])
        ->call('selectRecord', $rootA->id)
        ->assertSet('selectedRecordId', $rootA->id)
        ->assertSee('Bezeichnung');
});

it('creates nested set child nodes under the selected parent', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $rootA = NestedSetTreeNode::query()->where('label', 'Root A')->firstOrFail();

    Livewire::test(ResourceTreeIndex::class, ['configurationKey' => 'nested-set-reorderable'])
        ->call('selectRecord', $rootA->id)
        ->call('createChildNode')
        ->assertHasNoErrors();

    $child = NestedSetTreeNode::query()
        ->where('label', 'Neuer Eintrag')
        ->firstOrFail();

    $rootA->refresh();

    expect($child->parent_id)->toBe((int) $rootA->getKey())
        ->and($child->_rgt)->toBeGreaterThan($child->_lft)
        ->and($child->_lft)->toBeGreaterThan($rootA->_lft)
        ->and($child->_rgt)->toBeLessThan($rootA->_rgt);
});
