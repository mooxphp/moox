<?php

declare(strict_types=1);

use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;
use Moox\BlockEditor\EntityQuery\Support\FilterOptionsResolver;
use Moox\BlockEditor\Support\DynamicFeedEditorCatalog;

beforeEach(function (): void {
    EntityQuerySourceRegistry::clear();
    FilterOptionsResolver::clearCache();
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
                'options_resolver' => 'missing-resolver',
            ],
        ],
    ]);
});

afterEach(function (): void {
    EntityQuerySourceRegistry::clear();
    FilterOptionsResolver::clearCache();
});

it('builds editor catalog entries from registered sources', function (): void {
    $sources = DynamicFeedEditorCatalog::sources('de');

    expect($sources)->toHaveCount(1)
        ->and($sources[0]['key'])->toBe('demo')
        ->and($sources[0]['label'])->toBe('Demo Source')
        ->and($sources[0]['defaultView'])->toBe('card')
        ->and($sources[0]['filterSchema'])->toHaveKey('category_id')
        ->and($sources[0]['filterOptions'])->toHaveKey('category_id')
        ->and($sources[0]['filterOptions']['category_id'])->toBe([]);
});

it('resolves duplicate filter option resolvers only once per catalog build', function (): void {
    EntityQuerySourceRegistry::register('second', [
        'enabled' => true,
        'label' => 'Second Source',
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
                'options_resolver' => 'missing-resolver',
            ],
        ],
    ]);

    $resolver = new class extends FilterOptionsResolver
    {
        public static int $resolveCalls = 0;

        public function resolve(string $resolver, string $locale): array
        {
            self::$resolveCalls++;

            return parent::resolve($resolver, $locale);
        }
    };

    app()->instance(FilterOptionsResolver::class, $resolver);
    FilterOptionsResolver::clearCache();
    $resolver::$resolveCalls = 0;

    DynamicFeedEditorCatalog::sources('de');

    expect($resolver::$resolveCalls)->toBe(1);
});
