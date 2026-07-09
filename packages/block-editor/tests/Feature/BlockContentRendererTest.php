<?php

declare(strict_types=1);

use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;
use Moox\BlockEditor\Rendering\BlockContentRenderer;

afterEach(function (): void {
    EntityQuerySourceRegistry::clear();
});

it('renders supported blocks and ignores unknown dynamic feed sources gracefully', function (): void {
    $renderer = app(BlockContentRenderer::class);

    $html = $renderer->render([
        [
            'id' => '1',
            'type' => 'paragraph',
            'content' => '<p>Hello</p>',
        ],
        [
            'id' => '2',
            'type' => 'dynamicFeed',
            'sourceKey' => 'missing',
            'limit' => 5,
        ],
    ], 'de');

    expect($html)->toContain('Hello')
        ->and($html)->not->toContain('missing');
});

it('renders dynamic feed blocks when source and view are valid', function (): void {
    EntityQuerySourceRegistry::register('demo', [
        'enabled' => true,
        'model' => stdClass::class,
        'default_view' => 'card',
        'views' => [
            'card' => [
                'label' => 'Card',
                'view' => 'news::blocks.dynamic-feed.card',
            ],
        ],
        'filter_schema' => [],
        'sortable_columns' => [],
    ]);

    $renderer = app(BlockContentRenderer::class);

    $html = $renderer->render([
        [
            'id' => 'feed-1',
            'type' => 'dynamicFeed',
            'sourceKey' => 'demo',
            'limit' => 5,
            'view' => 'card',
            'filters' => [],
        ],
    ], 'de');

    expect(
        str_contains($html, 'Keine Eintr') || str_contains($html, 'No items')
    )->toBeTrue();
});
