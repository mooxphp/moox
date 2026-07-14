<?php

declare(strict_types=1);

use Moox\Page\Tests\TestCase;

pest()->extend(TestCase::class);

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Moox\Page\Database\Seeders\HomepageSeeder;
use Moox\Page\Models\Page;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

test('index renders configured homepage', function (): void {
    createPackageTestPage(layout: 'hecoform', slug: 'home-page', isStartpage: true);

    $view = callPageControllerIndex();

    expect($view->name())->toBe('hecoform.page');
});

test('homepage seeder creates exactly one default homepage', function (): void {
    (new HomepageSeeder)->run();

    expect(Page::query()->homepage()->count())->toBe(1);

    (new HomepageSeeder)->run();

    expect(Page::query()->homepage()->count())->toBe(1);
});

test('marking a new homepage unmarks the previous homepage', function (): void {
    $currentHomepage = createPackageTestPage(layout: 'default', slug: 'current-home', isStartpage: true);
    $nextHomepage = createPackageTestPage(layout: 'default', slug: 'next-home');

    $nextHomepage->update(['is_startpage' => true]);

    expect($currentHomepage->fresh()->is_startpage)->toBeFalse()
        ->and($nextHomepage->fresh()->is_startpage)->toBeTrue()
        ->and(Page::query()->homepage()->count())->toBe(1);
});

test('the only homepage cannot be removed without replacement', function (): void {
    $homepage = createPackageTestPage(layout: 'default', slug: 'only-home', isStartpage: true);

    expect(fn () => $homepage->update(['is_startpage' => false]))
        ->toThrow(ValidationException::class);
});

test('index falls back to published pages from all layouts when homepage is unpublished', function (): void {
    createPackageTestPage(
        layout: 'default',
        slug: 'draft-home',
        isStartpage: true,
        translationStatus: 'draft',
        publishedAt: null,
    );
    createPackageTestPage(layout: 'hecoform', slug: 'hecoform-page');

    $view = callPageControllerIndex();

    expect($view->name())->toBe('default.index')
        ->and($view->getData()['pages'])->toHaveCount(1)
        ->and($view->getData()['pages']->first()->translations->first()->slug)->toBe('hecoform-page');
});

test('controller renders view from database layout', function (): void {
    createPackageTestPage(layout: 'heltec', slug: 'brand-page');

    $view = callPageControllerShow('/brand-page');

    expect($view->name())->toBe('heltec.page');
});

test('controller renders hecoform layout from database', function (): void {
    createPackageTestPage(layout: 'hecoform', slug: 'hecoform-page');

    $view = callPageControllerShow('/hecoform-page');

    expect($view->name())->toBe('hecoform.page');
});

test('controller falls back to default view when layout is empty', function (): void {
    $page = createPackageTestPage(layout: 'default', slug: 'fallback-page');
    $page->update(['layout' => '']);

    $view = callPageControllerShow('/fallback-page');

    expect($view->name())->toBe('default.page');
});

test('controller returns not found for unpublished page', function (): void {
    createPackageTestPage(layout: 'heltec', slug: 'draft-page', translationStatus: 'draft', publishedAt: null);

    expect(fn () => callPageControllerShow('/draft-page'))
        ->toThrow(NotFoundHttpException::class);
});

test('controller returns not found for reserved slug', function (): void {
    $request = \Illuminate\Http\Request::create('/admin', 'GET');
    app()->instance('request', $request);

    expect(fn () => app(\Moox\Page\Http\Controllers\PageController::class)->show('admin'))
        ->toThrow(NotFoundHttpException::class);
});

test('controller resolves legacy pages permalink', function (): void {
    createPackageTestPage(layout: 'hecoform', slug: 'legacy-page');

    DB::table('page_translations')
        ->where('slug', 'legacy-page')
        ->update(['permalink' => '/pages/legacy-page']);

    $view = callPageControllerShow('/legacy-page');

    expect($view->name())->toBe('hecoform.page');
});

test('navigation only includes pages for current layout', function (): void {
    createPackageTestPage(layout: 'heltec', slug: 'heltec-only');
    createPackageTestPage(layout: 'heco', slug: 'heco-only');

    $view = callPageControllerShow('/heltec-only');

    expect($view->getData()['navigationPages'])->toHaveCount(1);
    expect($view->getData()['navigationPages']->first()->translations->first()->slug)->toBe('heltec-only');
});

test('page show route is registered', function (): void {
    expect(route('page.show', 'test-slug'))->toBe(url('/test-slug'));
});
