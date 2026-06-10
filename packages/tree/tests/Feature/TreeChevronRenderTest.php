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

it('renders expand chevrons for nodes with children', function (): void {
    $this->createTreeNodesTable();

    $root = TreeNode::query()->create(['label' => 'Root', 'sort_order' => 0]);
    TreeNode::query()->create(['label' => 'Child', 'parent_id' => $root->id, 'sort_order' => 10]);

    TreeIndexConfigurationRegistry::register(
        'chevron-tree',
        TreeIndexConfiguration::make(TreeNode::class)->reorderable(true),
    );

    config(['filament-tree-index.authorization.enabled' => false]);

    $component = Livewire::test(ResourceTreeIndex::class, [
        'configurationKey' => 'chevron-tree',
        'lang' => 'en',
        'search' => '',
    ]);

    $html = $component->html();

    expect($html)->not->toContain('<x-filament::icon-button')
        ->and($html)->toContain('fi-tree-chevron')
        ->and($html)->toContain('fi-icon-btn');

    $component->assertSee('Alle aufklappen');
});
