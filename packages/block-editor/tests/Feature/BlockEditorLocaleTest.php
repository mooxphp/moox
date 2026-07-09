<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Moox\BlockEditor\Support\BlockEditorLocale;

it('resolves locale from query lang first', function (): void {
    $request = Request::create('/page', 'GET', ['lang' => 'de_DE']);
    app()->setLocale('de');

    expect(BlockEditorLocale::resolveActive($request))->toBe('de_DE');
});

it('resolves short locales to translation locale variants when available', function (): void {
    $request = Request::create('/page', 'GET', ['lang' => 'en']);
    app()->setLocale('de');

    $resolved = BlockEditorLocale::resolveActive($request);

    expect($resolved)->toBeIn(['en', 'en_US']);
});

it('resolves locale from request input when query is empty', function (): void {
    $request = Request::create('/page', 'POST', ['lang' => 'fr_FR']);
    app()->setLocale('de');

    expect(BlockEditorLocale::resolveActive($request))->toBe('fr_FR');
});

it('falls back to app locale and config locale', function (): void {
    $request = Request::create('/page', 'GET');
    app()->setLocale('de');

    $resolved = BlockEditorLocale::resolveActive($request);
    expect($resolved)->toBeIn(['de', 'de_DE']);

    app()->setLocale('');
    config(['app.locale' => 'en']);

    $resolved = BlockEditorLocale::resolveActive($request);
    expect($resolved)->toBeIn(['en', 'en_US']);
});

it('builds locale candidates for short and variant locales', function (): void {
    $candidates = BlockEditorLocale::localeCandidates('de_DE');

    expect($candidates)->toContain('de_DE')
        ->and($candidates)->toContain('de');
});
