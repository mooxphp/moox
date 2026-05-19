<?php

declare(strict_types=1);

use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;
use Heco\FilamentTreeIndex\Config\TreeIndexConfigurationRegistry;
use Heco\FilamentTreeIndex\Livewire\ResourceTreeIndex;
use Heco\FilamentTreeIndex\Tests\Models\TreeNode;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function (): void {
    Schema::dropIfExists('tree_nodes');

    Schema::create('tree_nodes', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('parent_id')->nullable()->constrained('tree_nodes')->cascadeOnDelete();
        $table->string('label');
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });

    TreeIndexConfigurationRegistry::register(
        'test-tree',
        TreeIndexConfiguration::make(TreeNode::class)->labels(newRecordLabel: 'Neuer Eintrag'),
    );
});

it('creates a root tree node', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    Livewire::test(ResourceTreeIndex::class, ['configurationKey' => 'test-tree'])
        ->call('createRootNode')
        ->assertHasNoErrors();

    expect(TreeNode::query()->where('label', 'Neuer Eintrag')->exists())->toBeTrue();
});
