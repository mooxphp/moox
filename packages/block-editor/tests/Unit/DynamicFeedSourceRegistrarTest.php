<?php

declare(strict_types=1);

use Moox\BlockEditor\EntityQuery\DynamicFeedSourceRegistrar;
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;
use Moox\BlockEditor\Support\DynamicFeedEditorCatalog;

beforeEach(function (): void {
    EntityQuerySourceRegistry::clear();
});

afterEach(function (): void {
    EntityQuerySourceRegistry::clear();
    config()->set('moox-editor.dynamic_feed.sources', []);
});

it('registers dynamic feed sources from config', function (): void {
    config()->set('moox-editor.dynamic_feed.sources', [
        'articles' => [
            'enabled' => true,
            'label' => 'Articles',
            'default_view' => 'card',
            'views' => [
                'card' => [
                    'label' => 'Card',
                    'view' => 'news::blocks.dynamic-feed.card',
                ],
            ],
            'filter_schema' => [],
        ],
    ]);

    DynamicFeedSourceRegistrar::registerFromConfig();

    expect(EntityQuerySourceRegistry::has('articles'))->toBeTrue();

    $sources = DynamicFeedEditorCatalog::sources('de');

    expect($sources)->toHaveCount(1)
        ->and($sources[0]['key'])->toBe('articles')
        ->and($sources[0]['label'])->toBe('Articles');
});

it('skips disabled dynamic feed sources from config', function (): void {
    config()->set('moox-editor.dynamic_feed.sources', [
        'hidden' => [
            'enabled' => false,
            'label' => 'Hidden',
            'views' => [
                'card' => [
                    'label' => 'Card',
                    'view' => 'news::blocks.dynamic-feed.card',
                ],
            ],
        ],
        'visible' => [
            'enabled' => true,
            'label' => 'Visible',
            'default_view' => 'card',
            'views' => [
                'card' => [
                    'label' => 'Card',
                    'view' => 'news::blocks.dynamic-feed.card',
                ],
            ],
            'filter_schema' => [],
        ],
    ]);

    DynamicFeedSourceRegistrar::registerFromConfig();

    expect(EntityQuerySourceRegistry::has('hidden'))->toBeFalse()
        ->and(EntityQuerySourceRegistry::has('visible'))->toBeTrue()
        ->and(DynamicFeedEditorCatalog::sources('de'))->toHaveCount(1);
});
