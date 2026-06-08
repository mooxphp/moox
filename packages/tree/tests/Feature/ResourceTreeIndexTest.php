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

    TreeIndexConfigurationRegistry::register(
        'test-tree',
        TreeIndexConfiguration::make(TreeNode::class)->labels(newRecordLabel: 'Neuer Eintrag'),
    );
});

it('creates a root tree node', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'test-tree',
        'lang' => 'en',
        'search' => '',
    ])
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

    Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'test-tree-search',
        'lang' => 'en',
        'search' => 'Alpha',
    ])
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
            ->applyLanguageUsing(fn ($query, string $lang) => $query->where('label', 'like', $lang.':%')),
    );

    Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'test-tree-language',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('changeLanguage', 'de')
        ->assertSee('de: Root')
        ->assertDontSee('en: Root');
});
