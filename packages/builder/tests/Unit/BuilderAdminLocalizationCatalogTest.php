<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Illuminate\Support\Facades\DB;
use Moox\Builder\Support\BuilderAdminLocalizationCatalog;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('loads admin localizations once and resolves labels without extra queries', function (): void {
    $catalog = app(BuilderAdminLocalizationCatalog::class);

    if (! $catalog->isAvailable()) {
        expect(true)->toBeTrue();

        return;
    }

    DB::enableQueryLog();

    $first = $catalog->adminLocalizations();
    $second = $catalog->adminLocalizations();
    $allowed = $catalog->isAllowedAdminLocale((string) $first->first()?->locale_variant);
    $label = $catalog->labelFor($first->first());
    $flag = $catalog->flagFor($first->first());

    $localizationQueries = collect(DB::getQueryLog())->filter(
        fn (array $query): bool => str_contains($query['query'], 'localizations'),
    );

    expect($second)->toBe($first)
        ->and($allowed)->toBeTrue()
        ->and($label)->not->toBe('')
        ->and($flag)->toStartWith('flag-')
        ->and($localizationQueries)->toHaveCount(1);
});

it('treats unknown locales as not allowed when catalog is available', function (): void {
    $catalog = app(BuilderAdminLocalizationCatalog::class);

    if (! $catalog->isAvailable()) {
        expect(true)->toBeTrue();

        return;
    }

    expect($catalog->isAllowedAdminLocale('does-not-exist-xx'))->toBeFalse();
});

it('falls back to configured locales when the localization catalog is unavailable', function (): void {
    $catalog = app(BuilderAdminLocalizationCatalog::class);

    if ($catalog->isAvailable()) {
        expect(true)->toBeTrue();

        return;
    }

    config()->set('translatable.locales', ['en_US', 'de_CH']);
    config()->set('builder.default_locale', 'en_US');

    expect($catalog->isAllowedAdminLocale('en_US'))->toBeTrue()
        ->and($catalog->isAllowedAdminLocale('de_CH'))->toBeTrue()
        ->and($catalog->isAllowedAdminLocale('banana_XY'))->toBeFalse()
        ->and($catalog->isAllowedAdminLocale(''))->toBeFalse();
});

it('always allows the builder default locale in the catalog fallback', function (): void {
    $catalog = app(BuilderAdminLocalizationCatalog::class);

    if ($catalog->isAvailable()) {
        expect(true)->toBeTrue();

        return;
    }

    config()->set('translatable.locales', []);
    config()->set('builder.default_locale', 'fr_CH');

    $catalog = new BuilderAdminLocalizationCatalog(new BuilderLocaleResolver);

    expect($catalog->isAllowedAdminLocale('fr_CH'))->toBeTrue()
        ->and($catalog->isAllowedAdminLocale('en_US'))->toBeFalse();
});
