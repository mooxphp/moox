<?php

declare(strict_types=1);

use Livewire\Livewire;
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;
use Moox\BlockEditor\Livewire\BlockEditorField;

beforeEach(function (): void {
    EntityQuerySourceRegistry::clear();
    EntityQuerySourceRegistry::register('demo', [
        'enabled' => true,
        'label' => 'Demo Source',
        'default_view' => 'card',
        'views' => [
            'card' => [
                'label' => 'Card',
                'view' => 'news::blocks.dynamic-feed.card',
            ],
        ],
        'filter_schema' => [],
    ]);
});

afterEach(function (): void {
    EntityQuerySourceRegistry::clear();
});

it('embeds dynamic feed sources in the block editor field markup', function (): void {
    Livewire::test(BlockEditorField::class)
        ->assertSeeHtml('data-dynamic-feed-sources=')
        ->assertSeeHtml('"key":"demo"')
        ->assertSeeHtml('"label":"Demo Source"');
});
