<?php

declare(strict_types=1);

use Moox\BlockEditor\EntityQuery\EntityQueryDefinition;

beforeEach(function (): void {
    config([
        'moox-editor.dynamic_feed.max_limit' => 50,
        'moox-editor.dynamic_feed.default_limit' => 5,
        'moox-editor.dynamic_feed.default_order_by' => 'published_at',
        'moox-editor.dynamic_feed.default_order_direction' => 'desc',
    ]);
});

it('clamps limit between 1 and max_limit', function (): void {
    $definition = EntityQueryDefinition::fromArray(['sourceKey' => 'news', 'limit' => 999], 'de');

    expect($definition->limit)->toBe(50);

    $definition = EntityQueryDefinition::fromArray(['sourceKey' => 'news', 'limit' => 0], 'de');

    expect($definition->limit)->toBe(1);
});

it('normalizes order direction and builds from block payload', function (): void {
    $definition = EntityQueryDefinition::fromBlock([
        'sourceKey' => 'news',
        'limit' => 3,
        'orderBy' => 'title',
        'orderDirection' => 'ASC',
        'filters' => ['category_id' => 12],
    ], 'en');

    expect($definition->sourceKey)->toBe('news')
        ->and($definition->limit)->toBe(3)
        ->and($definition->orderBy)->toBe('title')
        ->and($definition->orderDirection)->toBe('asc')
        ->and($definition->filters)->toBe(['category_id' => 12])
        ->and($definition->locale)->toBe('en');
});
