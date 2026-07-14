<?php

declare(strict_types=1);

use Moox\Page\Tests\TestCase;

pest()->extend(TestCase::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Moox\Page\Support\PageLocaleResolver;

test('resolve returns default frontend localization', function (): void {
    $locale = app(PageLocaleResolver::class)->resolve();

    expect($locale)->toBe('en');
});

test('resolve matches locale from query parameter', function (): void {
    $this->seedGermanLocalization();

    $request = Request::create('/?lang=de', 'GET');
    app()->instance('request', $request);

    $locale = app(PageLocaleResolver::class)->resolve();

    expect($locale)->toBe('de');
});

test('resolve matches alpha2 language code', function (): void {
    $this->seedGermanLocalization();

    $request = Request::create('/?lang=de', 'GET');
    app()->instance('request', $request);

    expect(app(PageLocaleResolver::class)->resolve())->toBe('de');
});

test('candidates collects request and session values', function (): void {
    session(['locale' => 'en']);

    $request = Request::create('/?lang=de', 'GET');
    app()->instance('request', $request);

    expect(app(PageLocaleResolver::class)->candidates())->toBe(['de', 'de', 'en']);
});

test('resolve reads frontend localizations from cache without model serialization', function (): void {
    config(['page.cache.enabled' => true]);
    Cache::flush();

    $this->seedGermanLocalization();

    $resolver = app(PageLocaleResolver::class);

    expect($resolver->resolve())->toBe('en');

    $cached = Cache::get('page.localizations.frontend');

    expect($cached)->toBeArray()
        ->and($cached[0])->toHaveKeys(['locale_variant', 'is_default', 'alpha2'])
        ->and($cached[0])->not->toBeInstanceOf(\stdClass::class);

    $request = Request::create('/?lang=de', 'GET');
    app()->instance('request', $request);

    expect($resolver->resolve())->toBe('de');
});

test('stale model cache entries are replaced with plain arrays', function (): void {
    config(['page.cache.enabled' => true]);

    Cache::put('page.localizations.frontend', [
        (object) ['locale_variant' => 'en', 'is_default' => true],
    ], 3600);

    expect(app(PageLocaleResolver::class)->resolve())->toBe('en');

    $cached = Cache::get('page.localizations.frontend');

    expect($cached)->toBeArray()
        ->and($cached[0])->toBeArray()
        ->and($cached[0]['locale_variant'])->toBe('en');
});
