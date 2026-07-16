<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Illuminate\Support\Facades\DB;
use Moox\Builder\Support\BuilderAdminLocalizationCatalog;
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
