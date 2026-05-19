<?php

declare(strict_types=1);

use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Tree\Tests\Support\TestNestedSetTreeIndexResource;
use Moox\Tree\Tests\Support\TestTreeInspectorPage;

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
