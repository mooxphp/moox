<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Livewire\ResourceTreeIndex;
use Moox\Tree\Tests\Models\TreeNode;

beforeEach(function (): void {
    Schema::dropIfExists('tree_nodes');

    Schema::create('tree_nodes', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('parent_id')->nullable()->constrained('tree_nodes')->cascadeOnDelete();
        $table->string('label');
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });

    $root = TreeNode::query()->create(['label' => 'Root', 'sort_order' => 0]);
    $child = TreeNode::query()->create(['label' => 'Child', 'parent_id' => $root->id, 'sort_order' => 10]);

    TreeIndexConfigurationRegistry::register(
        'non-reorderable',
        TreeIndexConfiguration::make(TreeNode::class)->reorderable(false),
    );
});

it('ignores moveTreeNode when reordering is disabled', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $child = TreeNode::query()->where('label', 'Child')->firstOrFail();

    Livewire::test(ResourceTreeIndex::class, ['configurationKey' => 'non-reorderable'])
        ->call('moveTreeNode', $child->id, 0, null)
        ->assertHasNoErrors();

    expect(TreeNode::query()->findOrFail($child->id)->parent_id)->toBe(TreeNode::query()->where('label', 'Root')->value('id'));
});
