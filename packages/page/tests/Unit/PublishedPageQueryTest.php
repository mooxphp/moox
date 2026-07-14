<?php

declare(strict_types=1);

use Moox\Page\Tests\TestCase;

pest()->extend(TestCase::class);

use Moox\Page\Models\Page;
use Moox\Page\Support\PublishedPageQuery;

test('findBySlug returns published page', function (): void {
    createPackageTestPage(layout: 'default', slug: 'published-page');

    $page = app(PublishedPageQuery::class)->findBySlug('published-page', 'en');

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page?->translations->first()?->slug)->toBe('published-page');
});

test('findBySlug returns null for draft pages', function (): void {
    createPackageTestPage(
        layout: 'default',
        slug: 'draft-page',
        translationStatus: 'draft',
        publishedAt: null,
    );

    expect(app(PublishedPageQuery::class)->findBySlug('draft-page', 'en'))->toBeNull();
});

test('findHomepage returns startpage', function (): void {
    createPackageTestPage(layout: 'default', slug: 'home', isStartpage: true);
    createPackageTestPage(layout: 'default', slug: 'other-page');

    $homepage = app(PublishedPageQuery::class)->findHomepage('en');

    expect($homepage?->is_startpage)->toBeTrue()
        ->and($homepage?->translations->first()?->slug)->toBe('home');
});

test('forLayout only returns pages with matching layout', function (): void {
    createPackageTestPage(layout: 'heltec', slug: 'heltec-page');
    createPackageTestPage(layout: 'default', slug: 'default-page');

    $pages = app(PublishedPageQuery::class)->forLayout('en', 'heltec');

    expect($pages)->toHaveCount(1)
        ->and($pages->first()->translations->first()->slug)->toBe('heltec-page');
});

test('all returns published pages ordered by id', function (): void {
    createPackageTestPage(layout: 'default', slug: 'first');
    createPackageTestPage(layout: 'default', slug: 'second');

    $pages = app(PublishedPageQuery::class)->all('en');

    expect($pages)->toHaveCount(2)
        ->and($pages->first()->translations->first()->slug)->toBe('first');
});
