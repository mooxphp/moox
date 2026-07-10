<?php

declare(strict_types=1);

use Moox\BlockEditor\EntityQuery\Support\EagerLoadResolver;
use Moox\BlockEditor\EntityQuery\Support\FilterOptionsResolver;

it('builds locale-aware eager loads for configured relation paths', function (): void {
    $loads = app(EagerLoadResolver::class)->resolve([
        'category.translations',
        'translations.author',
    ], 'de');

    expect($loads)->toHaveKey('translations')
        ->and($loads)->toHaveKey('category')
        ->and($loads['translations'])->toBeCallable()
        ->and($loads['category'])->toBeCallable();
});

it('caches filter options per resolver and locale', function (): void {
    FilterOptionsResolver::clearCache();

    $resolver = app(FilterOptionsResolver::class);

    expect($resolver->resolve('unknown', 'de'))->toBe([])
        ->and($resolver->resolve('unknown', 'de'))->toBe([]);
});
