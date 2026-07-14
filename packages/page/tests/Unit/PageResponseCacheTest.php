<?php

declare(strict_types=1);

use Moox\Page\Tests\TestCase;

pest()->extend(TestCase::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Moox\Page\Support\PageResponseCache;

beforeEach(function (): void {
    config(['page.cache.enabled' => true]);
    Cache::flush();
});

test('remember returns resolver result when cache is disabled', function (): void {
    config(['page.cache.enabled' => false]);

    $result = app(PageResponseCache::class)->remember('en', 'slug', fn () => response('fresh'));

    expect($result->getContent())->toBe('fresh');
});

test('remember stores and returns cached html', function (): void {
    $cache = app(PageResponseCache::class);

    $first = $cache->remember('en', 'cached', fn () => response('<p>cached</p>'));
    expect($first->getContent())->toBe('<p>cached</p>');

    $second = $cache->remember('en', 'cached', fn () => response('<p>new</p>'));
    expect($second->getContent())->toBe('<p>cached</p>');
});

test('bumpVersion invalidates existing cache entries', function (): void {
    $cache = app(PageResponseCache::class);

    $cache->remember('en', 'versioned', fn () => response('<p>v1</p>'));
    $cache->bumpVersion();

    $result = $cache->remember('en', 'versioned', fn () => response('<p>v2</p>'));
    expect($result->getContent())->toBe('<p>v2</p>');
});

test('remember skips cache when lang query is present', function (): void {
    $request = Request::create('/?lang=de', 'GET');
    app()->instance('request', $request);

    $calls = 0;
    $cache = app(PageResponseCache::class);

    $cache->remember('en', 'lang', function () use (&$calls) {
        $calls++;

        return response('<p>fresh</p>');
    });
    $cache->remember('en', 'lang', function () use (&$calls) {
        $calls++;

        return response('<p>fresh</p>');
    });

    expect($calls)->toBe(2);
});
('<p>fresh</p>');
    });

    expect($calls)->toBe(2);
});
