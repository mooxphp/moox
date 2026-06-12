<?php

declare(strict_types=1);

use Livewire\Livewire;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Tests\Models\TreeNode;
use Moox\Tree\Tests\Support\CreatesTreeNodesTable;
use Moox\Tree\Tests\Support\TestForwardTreeResource;
use Moox\Tree\Tests\Support\TestTreeIndexHost;
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

    Livewire::test(TestTreeIndexHost::class, [
        'treeIndexConfigurationKey' => 'test-tree',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('createRootNode')
        ->assertHasNoErrors();

    expect(TreeNode::query()->where('label', 'Neuer Eintrag')->exists())->toBeTrue();
});

it('opens create inspector mode when resource create inspector is configured', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    TreeIndexConfigurationRegistry::register(
        'test-tree-create-inspector',
        TestForwardTreeResource::treeIndexWithInspector()
            ->toolbarSearch(false)
            ->toolbarLanguageSwitcher(false),
    );

    Livewire::test(TestTreeIndexHost::class, [
        'treeIndexConfigurationKey' => 'test-tree-create-inspector',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('createRootNode')
        ->assertSet('isCreatingInspector', true)
        ->assertSeeHtml('id="form"')
        ->assertHasNoErrors();
});

it('exposes the inspector record via getRecord for resource form fields', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $node = TreeNode::query()->create(['label' => 'Inspector Node', 'sort_order' => 10]);

    Livewire::test(TestTreeIndexHost::class, [
        'treeIndexConfigurationKey' => 'test-tree',
        'lang' => 'en',
        'search' => '',
    ])
        ->set('record', $node)
        ->tap(function ($component) use ($node): void {
            expect($component->instance()->getRecord()?->getKey())->toBe($node->getKey());
            expect($component->instance()->hasRecord())->toBeTrue();
        })
        ->assertHasNoErrors();
});

it('filters visible nodes by search term', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    TreeNode::query()->create(['label' => 'Alpha', 'sort_order' => 10]);
    TreeNode::query()->create(['label' => 'Beta', 'sort_order' => 20]);

    TreeIndexConfigurationRegistry::register(
        'test-tree-search',
        TreeIndexConfiguration::make(TreeNode::class)->toolbarSearch(),
    );

    Livewire::test(TestTreeIndexHost::class, [
        'treeIndexConfigurationKey' => 'test-tree-search',
        'lang' => 'en',
        'search' => 'Alpha',
    ])
        ->set('search', 'Alpha')
        ->assertSee('Alpha')
        ->assertDontSee('Beta');
});

it('keeps the selected record when changing language from the tree toolbar', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    $record = TreeNode::query()->create(['label' => 'Selected', 'sort_order' => 10]);

    Livewire::test(TestTreeIndexHost::class, [
        'treeIndexConfigurationKey' => 'test-tree',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('selectRecord', (int) $record->getKey())
        ->assertSet('treeSelectedId', (int) $record->getKey())
        ->call('changeLanguage', 'de')
        ->assertSet('treeSelectedId', (int) $record->getKey())
        ->assertSet('lang', 'de');
});

it('validates required fields before creating via inspector form', function (): void {
    config(['filament-tree-index.authorization.enabled' => false]);

    TreeIndexConfigurationRegistry::register(
        'test-tree-validation',
        TestForwardTreeResource::treeIndexWithInspector()
            ->toolbarSearch(false)
            ->toolbarLanguageSwitcher(false),
    );

    Livewire::test(TestTreeIndexHost::class, [
        'treeIndexConfigurationKey' => 'test-tree-validation',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('createRootNode')
        ->assertSet('isCreatingInspector', true)
        ->call('create')
        ->assertHasErrors(['data.label' => 'required']);

    expect(TreeNode::query()->count())->toBe(0);
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

    Livewire::test(TestTreeIndexHost::class, [
        'treeIndexConfigurationKey' => 'test-tree-language',
        'lang' => 'en',
        'search' => '',
    ])
        ->call('changeLanguage', 'de')
        ->assertSee('de: Root')
        ->assertDontSee('en: Root');
});
