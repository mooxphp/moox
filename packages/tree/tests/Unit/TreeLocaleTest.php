<?php

declare(strict_types=1);

use Moox\Tree\Support\TreeLocale;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class);

it('resolves active language from request input', function (): void {
    request()->merge(['lang' => 'de_DE']);

    expect(TreeLocale::resolveActiveLanguage())->toBe('de_DE');
});

it('builds locale candidates with variants', function (): void {
    $candidates = TreeLocale::localeCandidates('de-DE');

    expect($candidates)->toContain('de-DE')
        ->and($candidates)->toContain('de_DE')
        ->and($candidates)->toContain('de');
});

it('syncs application locale from language code', function (): void {
    TreeLocale::syncApplicationLocale('en_US');

    expect(app()->getLocale())->toBe('en_US');
});

it('syncs language to request', function (): void {
    TreeLocale::syncToRequest('de_DE');

    expect(request()->input('lang'))->toBe('de_DE');
});

it('falls back to app locale when no request language is set', function (): void {
    config(['app.locale' => 'en']);

    expect(TreeLocale::resolveDefaultLocale())->toBe('en');
});

it('builds index url parameters with default lang', function (): void {
    config(['app.locale' => 'cs_CZ']);

    expect(TreeLocale::indexUrlParameters())->toBe(['lang' => 'cs_CZ']);
});

it('preserves request lang when building index url parameters', function (): void {
    request()->query->replace(['lang' => 'de_DE', 'tab' => 'all']);

    expect(TreeLocale::indexUrlParameters())->toBe(['lang' => 'de_DE', 'tab' => 'all']);
});

it('merges overrides when building index url parameters', function (): void {
    config(['app.locale' => 'cs_CZ']);

    expect(TreeLocale::indexUrlParameters(['tab' => 'all']))
        ->toBe(['lang' => 'cs_CZ', 'tab' => 'all']);
});

it('detects missing canonical index parameters for lang and tab', function (): void {
    config(['app.locale' => 'cs_CZ']);

    expect(TreeLocale::missingCanonicalIndexParameters(ensureTab: true))
        ->toBe(['lang' => 'cs_CZ', 'tab' => 'all']);
});

it('detects only missing lang when tab is already present', function (): void {
    request()->query->replace(['tab' => 'all']);
    config(['app.locale' => 'cs_CZ']);

    expect(TreeLocale::missingCanonicalIndexParameters(ensureTab: true))
        ->toBe(['tab' => 'all', 'lang' => 'cs_CZ']);
});

it('returns null when canonical index parameters are already present', function (): void {
    request()->query->replace(['lang' => 'de_DE', 'tab' => 'all']);

    expect(TreeLocale::missingCanonicalIndexParameters(ensureTab: true))->toBeNull();
});

it('builds language change parameters with tab', function (): void {
    expect(TreeLocale::languageChangeParameters('de', 'deleted'))
        ->toBe(['lang' => 'de', 'tab' => 'deleted']);
});

it('builds language change parameters with selected record', function (): void {
    expect(TreeLocale::languageChangeParameters('de', null, 42))
        ->toBe(['lang' => 'de', 'selected' => '42']);
});

it('preserves existing query parameters when building language change parameters', function (): void {
    request()->query->replace([
        'lang' => 'de_DE',
        'tab' => 'all',
        'selected' => '7',
    ]);

    expect(TreeLocale::languageChangeParameters('en'))
        ->toBe([
            'lang' => 'en',
            'tab' => 'all',
            'selected' => '7',
        ]);
});

it('clears selected when an explicit null selected record is provided', function (): void {
    request()->query->set('selected', '7');

    expect(TreeLocale::languageChangeParameters('en', null, null))
        ->toBe(['lang' => 'en']);
});

it('returns empty candidates for blank language', function (): void {
    expect(TreeLocale::localeCandidates(''))->toBe([]);
});
