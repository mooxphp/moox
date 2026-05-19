<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Tests\Models\TreeNode;

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
