<?php

declare(strict_types=1);

require_once __DIR__.'/../Support/MediaTestingDatabase.php';

use Moox\Media\Support\MediaLocaleResolver;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    configureMediaTestingDatabase();
    config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);
});

it('normalizes bare app locales to admin region variants', function (): void {
    config(['app.locale' => 'en']);

    expect(app(MediaLocaleResolver::class)->adminDefaultLocale())->toBe('en_US');
});

it('builds a fallback chain with preferred and base locales', function (): void {
    config(['app.locale' => 'de', 'app.fallback_locale' => 'en']);

    $chain = app(MediaLocaleResolver::class)->fallbackChain('de_AT');

    expect($chain)->toContain('de_AT')
        ->and($chain)->toContain('de')
        ->and($chain)->toContain('de_DE')
        ->and($chain)->toContain('en_US');
});

it('restores the previous locale after withLocale', function (): void {
    app()->setLocale('en');

    $result = app(MediaLocaleResolver::class)->withLocale('de_DE', function (): string {
        expect(app()->getLocale())->toBe('de_DE');

        return 'ok';
    });

    expect($result)->toBe('ok')
        ->and(app()->getLocale())->toBe('en');
});

it('uses the switcher lang instead of falling through to german', function (): void {
    config(['app.locale' => 'de']);
    request()->merge(['lang' => 'en_US']);

    $resolver = app(MediaLocaleResolver::class);

    expect($resolver->currentLocale())->toBe('en_US')
        ->and($resolver->localeVariants('en_US'))->toContain('en')
        ->and($resolver->localeVariants('en_US'))->not->toContain('de_DE');
});
