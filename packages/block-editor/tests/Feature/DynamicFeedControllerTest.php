<?php

declare(strict_types=1);

use App\Models\User;
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;

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
        'filter_schema' => [
            'category_id' => [
                'type' => 'select',
                'label' => 'Category',
                'nullable' => true,
                'options_resolver' => 'category',
            ],
        ],
    ]);
});

afterEach(function (): void {
    EntityQuerySourceRegistry::clear();
});

it('registers dynamic feed api routes', function (): void {
    expect(route('moox-editor.dynamic-feeds.sources'))->toContain('dynamic-feeds/sources')
        ->and(route('moox-editor.dynamic-feeds.preview'))->toContain('dynamic-feeds/preview');
});

it('returns registered sources for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('moox-editor.dynamic-feeds.sources'))
        ->assertOk()
        ->assertJsonPath('data.0.key', 'demo')
        ->assertJsonPath('data.0.label', 'Demo Source')
        ->assertJsonPath('data.0.defaultView', 'card');
});

it('returns views and filter options for a source', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('moox-editor.dynamic-feeds.views', ['sourceKey' => 'demo']))
        ->assertOk()
        ->assertJsonPath('data.0.key', 'card');

    $this->actingAs($user)
        ->getJson(route('moox-editor.dynamic-feeds.filter-options', [
            'sourceKey' => 'demo',
            'filter' => 'category_id',
            'lang' => 'de',
        ]))
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('denies guests on dynamic feed api routes', function (): void {
    $this->getJson(route('moox-editor.dynamic-feeds.sources'))->assertUnauthorized();
});
