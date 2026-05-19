<?php

declare(strict_types=1);

use Heco\FilamentTreeIndex\Contracts\ConfiguresTreeIndex;
use Heco\FilamentTreeIndex\Tests\Support\TestNestedSetTreeIndexResource;
use Heco\FilamentTreeIndex\Tests\Support\TestTreeInspectorPage;

it('supports nested set resources with a dedicated inspector page', function (): void {
    expect(class_implements(TestNestedSetTreeIndexResource::class))
        ->toContain(ConfiguresTreeIndex::class);

    $configuration = TestNestedSetTreeIndexResource::treeIndex();

    expect($configuration->isReorderable())->toBeFalse()
        ->and($configuration->isLabelColumnQueryable())->toBeFalse()
        ->and($configuration->usesNestedSet())->toBeTrue()
        ->and($configuration->getSortColumn())->toBe('_lft')
        ->and($configuration->getLabelColumn())->toBe('title')
        ->and($configuration->getInspectorPageClass())->toBe(TestTreeInspectorPage::class);
});
