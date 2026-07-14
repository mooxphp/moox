<?php

declare(strict_types=1);

use Moox\Page\Tests\TestCase;

pest()->extend(TestCase::class);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Moox\Page\Models\Page;
use Moox\Page\Support\PageResponseCache;

beforeEach(function (): void {
    config(['page.cache.enabled' => true]);
    Cache::flush();
});

test('response cache stores rendered html for show requests', function (): void {
    createPackageTestPage(layout: 'default', slug: 'cached-page');

    $first = callPageControllerShow('/cached-page');
    expect($first)->not->toBeInstanceOf(Response::class);

    $second = callPageControllerShow('/cached-page');

    expect($second)->toBeInstanceOf(Response::class)
        ->and($second->getContent())->toContain('cached-page');
});

test('response cache stores rendered html for index requests', function (): void {
    createPackageTestPage(layout: 'default', slug: 'home', isStartpage: true);

    $first = callPageControllerIndex();
    expect($first)->not->toBeInstanceOf(Response::class);

    $second = callPageControllerIndex();

    expect($second)->toBeInstanceOf(Response::class);
});

test('page update invalidates response cache', function (): void {
    $page = createPackageTestPage(layout: 'default', slug: 'invalidate-me');

    callPageControllerShow('/invalidate-me');
    expect(callPageControllerShow('/invalidate-me'))->toBeInstanceOf(Response::class);

    $page->update(['layout' => 'heltec']);

    $afterInvalidation = callPageControllerShow('/invalidate-me');

    expect($afterInvalidation)->not->toBeInstanceOf(Response::class)
        ->and($afterInvalidation->name())->toBe('heltec.page');
});

test('cache is skipped for authenticated users', function (): void {
    createPackageTestPage(layout: 'default', slug: 'auth-page');

    $user = new class extends \Illuminate\Foundation\Auth\User
    {
        protected $table = 'users';
    };

    $this->actingAs($user);

    callPageControllerShow('/auth-page');
    $second = callPageControllerShow('/auth-page');

    expect($second)->not->toBeInstanceOf(Response::class);
});

test('cache is skipped when lang query parameter is present', function (): void {
    createPackageTestPage(layout: 'default', slug: 'lang-page');

    $request = Request::create('/lang-page?lang=de', 'GET');
    app()->instance('request', $request);

    app(PageResponseCache::class)->remember(
        locale: 'en',
        slug: 'lang-page',
        resolver: fn () => response('fresh'),
    );

    $cached = Cache::get('page.response.'.md5('en|lang-page|0'));

    expect($cached)->toBeNull();
});

test('forgetAll bumps cache version', function (): void {
    $cache = app(PageResponseCache::class);

    $cache->remember('en', 'version-test', fn () => response('v1'));
    $cache->forgetAll();

    createPackageTestPage(layout: 'default', slug: 'version-test');

    $result = callPageControllerShow('/version-test');

    expect($result)->not->toBeInstanceOf(Response::class);
});
ControllerShow('/version-test');

    expect($result)->not->toBeInstanceOf(Response::class);
});
