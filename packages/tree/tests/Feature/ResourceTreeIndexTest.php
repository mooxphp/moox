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

it('filters visible nodes by search term', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    TreeNode::query()->create(['label' => 'Alpha', 'sort_order' => 10]);
    TreeNode::query()->create(['label' => 'Beta', 'sort_order' => 20]);

    TreeIndexConfigurationRegistry::register(
        'test-tree-search',
        TreeIndexConfiguration::make(TreeNode::class)->toolbarSearch(),
    );

    Livewire::test(ResourceTreeIndex::class, ['configurationKey' => 'test-tree-search'])
        ->set('search', 'Alpha')
        ->assertSee('Alpha')
        ->assertDontSee('Beta');
});

it('applies a custom language callback when language changes', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    TreeNode::query()->create(['label' => 'de: Root', 'sort_order' => 10]);
    TreeNode::query()->create(['label' => 'en: Root', 'sort_order' => 20]);

    TreeIndexConfigurationRegistry::register(
        'test-tree-language',
        TreeIndexConfiguration::make(TreeNode::class)
            ->toolbarLanguageSwitcher()
            ->applyLanguageUsing(fn ($query, string $lang) => $query->where('label', 'like', $lang.':%')),
    );

    Livewire::test(ResourceTreeIndex::class, ['configurationKey' => 'test-tree-language'])
        ->call('changeLanguage', 'de')
        ->assertSee('de: Root')
        ->assertDontSee('en: Root');
});
