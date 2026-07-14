<?php

declare(strict_types=1);

use Moox\Page\Tests\TestCase;

pest()->extend(TestCase::class);

use Illuminate\Validation\ValidationException;
use Moox\Page\Models\Page;

test('page can be created with translations', function (): void {
    $page = createPackageTestPage(layout: 'default', slug: 'english-title', locale: 'en');

    $page->translations()->create([
        'locale' => 'de',
        'title' => 'Deutscher Titel',
        'slug' => 'deutscher-titel',
        'permalink' => '/deutscher-titel',
        'description' => 'Deutsche Beschreibung',
        'content' => [],
        'translation_status' => 'published',
        'published_at' => now(),
    ]);

    expect($page->hasTranslation('en'))->toBeTrue()
        ->and($page->hasTranslation('de'))->toBeTrue()
        ->and($page->getAvailableTranslations())->toContain('en', 'de');
});

test('page layout can be updated', function (): void {
    $page = createPackageTestPage(layout: 'heltec', slug: 'layout-page');

    $page->update(['layout' => 'default']);

    expect($page->fresh()->layout)->toBe('default');
});

test('homepage cannot be removed without replacement', function (): void {
    $homepage = createPackageTestPage(layout: 'default', slug: 'only-home', isStartpage: true);

    expect(fn () => $homepage->update(['is_startpage' => false]))
        ->toThrow(ValidationException::class);
});
