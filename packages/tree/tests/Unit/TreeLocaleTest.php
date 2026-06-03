<?php

declare(strict_types=1);

use Moox\Tree\Support\TreeLocale;

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

it('returns empty candidates for blank language', function (): void {
    expect(TreeLocale::localeCandidates(''))->toBe([]);
});
