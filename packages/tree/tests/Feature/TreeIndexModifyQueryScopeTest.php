<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Tree\Actions\Tree\CreateNestedSetTreeNodeAction;
use Moox\Tree\Actions\Tree\CreateTreeNodeAction;
use Moox\Tree\Actions\Tree\MoveNestedSetTreeNodeAction;
use Moox\Tree\Actions\Tree\MoveTreeNodeAction;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Tests\Models\NestedSetTreeNode;
use Moox\Tree\Tests\Models\TreeNode;

beforeEach(function (): void {
    Schema::dropIfExists('tree_nodes');
    Schema::dropIfExists('nested_set_tree_nodes');

    Schema::create('tree_nodes', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('parent_id')->nullable()->constrained('tree_nodes')->cascadeOnDelete();
        $table->string('label');
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_visible')->default(true);
        $table->timestamps();
    });

    Schema::create('nested_set_tree_nodes', function (Blueprint $table): void {
        $table->id();
        $table->string('label');
        $table->boolean('is_visible')->default(true);
        $table->nestedSet();
        $table->timestamps();
    });
});

function visibleTreeConfiguration(): TreeIndexConfiguration
{
    return TreeIndexConfiguration::make(TreeNode::class)
        ->modifyQuery(fn (Builder $query): Builder => $query->where('is_visible', true));
}

function visibleNestedSetConfiguration(): TreeIndexConfiguration
{
    return TreeIndexConfiguration::make(NestedSetTreeNode::class)
        ->nestedSet()
        ->sortColumn('_lft')
        ->modifyQuery(fn (Builder $query): Builder => $query->where('is_visible', true));
}

it('assigns sort order from visible siblings only when creating a tree node', function (): void {
    $parent = TreeNode::query()->create(['label' => 'Parent', 'sort_order' => 0, 'is_visible' => true]);
    TreeNode::query()->create(['label' => 'Visible child', 'parent_id' => $parent->id, 'sort_order' => 10, 'is_visible' => true]);
    TreeNode::query()->create(['label' => 'Hidden child', 'parent_id' => $parent->id, 'sort_order' => 90, 'is_visible' => false]);

    $record = app(CreateTreeNodeAction::class, ['configuration' => visibleTreeConfiguration()])
        ->handle((int) $parent->getKey());

    expect($record->sort_order)->toBe(20)
        ->and($record->parent_id)->toBe((int) $parent->getKey());
});

it('reorders only visible siblings when moving a tree node', function (): void {
    $first = TreeNode::query()->create(['label' => 'First', 'sort_order' => 0, 'is_visible' => true]);
    $second = TreeNode::query()->create(['label' => 'Second', 'sort_order' => 10, 'is_visible' => true]);
    TreeNode::query()->create(['label' => 'Hidden', 'sort_order' => 5, 'is_visible' => false]);

    app(MoveTreeNodeAction::class, ['configuration' => visibleTreeConfiguration()])
        ->handle($second, null, 0);

    expect(TreeNode::query()->findOrFail($second->id)->sort_order)->toBe(0)
        ->and(TreeNode::query()->findOrFail($first->id)->sort_order)->toBe(10)
        ->and(TreeNode::query()->where('label', 'Hidden')->value('sort_order'))->toBe(5);
});

it('cannot attach a nested set node to a parent outside the query scope', function (): void {
    NestedSetTreeNode::query()->create(['label' => 'Visible root', 'is_visible' => true]);
    $hiddenRoot = NestedSetTreeNode::query()->create(['label' => 'Hidden root', 'is_visible' => false]);

    app(CreateNestedSetTreeNodeAction::class, ['configuration' => visibleNestedSetConfiguration()])
        ->handle((int) $hiddenRoot->getKey());
})->throws(ModelNotFoundException::class);

it('reorders nested set roots using only visible siblings for positioning', function (): void {
    $rootA = NestedSetTreeNode::query()->create(['label' => 'Root A', 'is_visible' => true]);
    $rootB = NestedSetTreeNode::query()->create(['label' => 'Root B', 'is_visible' => true]);
    NestedSetTreeNode::query()->create(['label' => 'Hidden root', 'is_visible' => false]);

    expect($rootA->_lft)->toBeLessThan($rootB->_lft);

    app(MoveNestedSetTreeNodeAction::class, ['configuration' => visibleNestedSetConfiguration()])
        ->handle($rootB, null, 0);

    $rootA->refresh();
    $rootB->refresh();

    expect($rootB->_lft)->toBeLessThan($rootA->_lft);
});
