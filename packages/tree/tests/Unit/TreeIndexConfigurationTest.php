<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Filament\Concerns\RendersAsTreeIndexCreateInspector;
use Moox\Tree\Filament\Pages\TreeIndexCreateInspectorPageFactory;
use Moox\Tree\Tests\Models\TreeNode;
use Moox\Tree\Tests\Support\CreatesTreeNodesTable;
use Moox\Tree\Tests\Support\TestCreateTreeNodePage;
use Moox\Tree\Tests\Support\TestForwardTreeResource;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class, CreatesTreeNodesTable::class);

beforeEach(function (): void {
    $this->createTreeNodesTable();
});

it('can disable reordering', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)->reorderable(false);

    expect($configuration->isReorderable())->toBeFalse();
});

it('can exclude the label column from tree queries', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->labelColumn('title')
        ->labelColumnQueryable(false);

    expect($configuration->treeSelectColumns())->toBe(['id', 'parent_id', 'sort_order']);
});

it('includes nested set columns in tree queries', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->nestedSet()
        ->labelColumnQueryable(false)
        ->sortColumn('_lft');

    expect($configuration->treeSelectColumns())->toBe(['id', 'parent_id', '_lft', '_rgt']);
});

it('can modify the eloquent query', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->modifyQuery(fn (Builder $query): Builder => $query->where('label', 'Root'));

    $sql = $configuration->applyQuery(TreeNode::query())->toSql();

    expect($sql)->toContain('"label" = ?');
});

it('builds a scoped base query via newQuery', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->modifyQuery(fn (Builder $query): Builder => $query->where('label', 'Root'));

    $sql = $configuration->newQuery()->toSql();

    expect($sql)->toContain('"label" = ?');
});

it('scopes sibling queries to the configured base query', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->modifyQuery(fn (Builder $query): Builder => $query->where('label', 'like', 'Visible%'));

    $sql = $configuration->siblingsQuery(null)->toSql();

    expect($sql)->toContain('"label" like')
        ->and($sql)->toContain('"parent_id" is null');
});

it('can enable toolbar search and language switcher', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->toolbarSearch()
        ->toolbarLanguageSwitcher();

    expect($configuration->isToolbarSearchEnabled())->toBeTrue()
        ->and($configuration->isToolbarLanguageSwitcherEnabled())->toBeTrue();
});

it('applies default search against the label column', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class);

    $sql = $configuration->applySearch(TreeNode::query(), 'Root')->toSql();

    expect($sql)->toContain('"label" like ?');
});

it('can forward list capabilities from a resource class', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->forwardFromResource(TestForwardTreeResource::class);

    expect($configuration->getSourceResourceClass())->toBe(TestForwardTreeResource::class)
        ->and($configuration->isToolbarSearchEnabled())->toBeTrue()
        ->and($configuration->isToolbarLanguageSwitcherEnabled())->toBeTrue()
        ->and($configuration->usesFilamentTableToolbar())->toBeFalse();

    expect($configuration->newQuery()->toSql())->toContain('tree_nodes');
});

it('places language switcher in the filament table toolbar when forwarding with table toolbar', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->forwardFromResource(TestForwardTreeResource::class, useFilamentTableToolbar: true);

    expect($configuration->usesFilamentTableToolbar())->toBeTrue()
        ->and($configuration->isToolbarSearchEnabled())->toBeFalse()
        ->and($configuration->isToolbarLanguageSwitcherEnabled())->toBeFalse()
        ->and($configuration->isFilamentTableLanguageSwitcherEnabled())->toBeTrue();

    $sql = $configuration->applySearch(TreeNode::query(), 'Alpha')->toSql();

    expect($sql)->toContain('"label" like');
});

it('can enable localized translation toolbar scopes', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->toolbarLocalizedTranslations(translationSearchColumn: 'label');

    expect($configuration->isToolbarSearchEnabled())->toBeTrue()
        ->and($configuration->isToolbarLanguageSwitcherEnabled())->toBeTrue()
        ->and($configuration->getApplySearchUsing())->not->toBeNull()
        ->and($configuration->getApplyLanguageUsing())->not->toBeNull();
});

it('can register an explicit create inspector page override', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->inspectorCreatePage(TestCreateTreeNodePage::class);

    expect($configuration->usesResourceCreateInspector())->toBeTrue()
        ->and($configuration->getInspectorCreatePageClass())->toBe(TestCreateTreeNodePage::class);
});

it('uses the resource create form by default when an inspector is configured', function (): void {
    $configuration = TestForwardTreeResource::treeIndexWithInspector();

    expect($configuration->usesResourceCreateInspector())->toBeTrue();
});

it('can opt out of the resource create inspector with stub create', function (): void {
    $configuration = TestForwardTreeResource::treeIndexWithInspector()->stubCreate();

    expect($configuration->usesResourceCreateInspector())->toBeFalse();
});

it('resolves a generated create inspector page from the source resource', function (): void {
    $configurationKey = 'test-generated-create-inspector';
    TreeIndexConfigurationRegistry::forget($configurationKey);
    TreeIndexConfigurationRegistry::register($configurationKey, TestForwardTreeResource::treeIndexWithInspector());

    $resolvedClass = TreeIndexCreateInspectorPageFactory::resolve($configurationKey);

    expect($resolvedClass)->not->toBe(TestCreateTreeNodePage::class)
        ->and(is_subclass_of($resolvedClass, TestCreateTreeNodePage::class))->toBeTrue()
        ->and(in_array(RendersAsTreeIndexCreateInspector::class, class_uses($resolvedClass), true))->toBeTrue();
});

it('can disable the filament table language switcher', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->forwardFromResource(TestForwardTreeResource::class, useFilamentTableToolbar: true)
        ->filamentTableLanguageSwitcher(false);

    expect($configuration->isFilamentTableLanguageSwitcherEnabled())->toBeFalse();
});

it('can apply custom search and language scopes', function (): void {
    $configuration = TreeIndexConfiguration::make(TreeNode::class)
        ->applySearchUsing(fn (Builder $query, string $search, TreeIndexConfiguration $config): Builder => $query->where($config->getLabelColumn(), 'like', $search.'%'))
        ->applyLanguageUsing(fn (Builder $query, string $lang, TreeIndexConfiguration $config): Builder => $query->where($config->getLabelColumn(), 'like', $lang.':%'));

    $sql = $configuration
        ->applyLanguage(
            $configuration->applySearch(TreeNode::query(), 'Visible'),
            'de',
        )
        ->toSql();

    expect($sql)->toContain('"label" like ?');
});
