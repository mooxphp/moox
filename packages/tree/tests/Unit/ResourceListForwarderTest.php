<?php

declare(strict_types=1);

use Moox\Tree\Support\ResourceListForwarder;
use Moox\Tree\Tests\Models\TreeNode;
use Moox\Tree\Tests\Support\TestForwardTreeResource;

it('syncs language without narrowing the query', function (): void {
    request()->merge(['lang' => '']);

    $sqlBefore = TreeNode::query()->toSql();
    $sqlAfter = ResourceListForwarder::applyLanguage(TestForwardTreeResource::class, TreeNode::query(), 'de_DE')->toSql();

    expect($sqlAfter)->toBe($sqlBefore)
        ->and(app()->getLocale())->toBe('de_DE');
});

it('applies search without a mounted filament table column', function (): void {
    $sql = ResourceListForwarder::applySearch(
        TestForwardTreeResource::class,
        TreeNode::query(),
        'Alpha',
        '',
    )->toSql();

    expect($sql)->toContain('"label" like');
});
