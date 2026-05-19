<?php

declare(strict_types=1);

use Heco\FilamentTreeIndex\Config\TreeIndexConfigurationRegistry;
use Heco\FilamentTreeIndex\Tests\Models\TreeNode;
use Heco\FilamentTreeIndex\Tests\Support\TestTreeIndexResource;

it('resolves tree index configuration from the resource class on demand', function (): void {
    $key = TestTreeIndexResource::class;

    TreeIndexConfigurationRegistry::forget($key);

    $configuration = TreeIndexConfigurationRegistry::get($key);

    expect($configuration->modelClass())->toBe(TreeNode::class);
});

it('throws when the configuration key is not a tree index resource', function (): void {
    TreeIndexConfigurationRegistry::forget(TreeNode::class);

    TreeIndexConfigurationRegistry::get(TreeNode::class);
})->throws(LogicException::class);
