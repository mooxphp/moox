<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Illuminate\Http\Request;
use Moox\Builder\Http\Middleware\ResolveBuilderAdminLocale;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

uses(TestCase::class);

it('stores a valid admin locale for field group admin routes', function (): void {
    $middleware = app(ResolveBuilderAdminLocale::class);

    $request = Request::create('/admin/field-groups/1/edit', 'GET', ['lang' => 'de_CH']);

    $middleware->handle($request, fn (Request $request): Response => response('ok'));

    expect(session(BuilderLocaleResolver::ADMIN_SESSION_KEY))->toBe('de_CH')
        ->and($request->input('lang'))->toBe('de_CH')
        ->and(app(BuilderLocaleResolver::class)->current())->toBe('de_CH');
});

it('ignores locale switching on non-translatable custom field resources', function (): void {
    $middleware = app(ResolveBuilderAdminLocale::class);

    $request = Request::create('/admin/items/1/edit', 'GET', ['lang' => 'de_CH']);

    $middleware->handle($request, fn (Request $request): Response => response('ok'));

    expect(session(BuilderLocaleResolver::ADMIN_SESSION_KEY))->toBeNull()
        ->and($request->input('lang'))->toBe('de_CH');
});

it('restores locale from session on livewire subrequests for field groups', function (): void {
    session([BuilderLocaleResolver::ADMIN_SESSION_KEY => 'de_CH']);

    $middleware = app(ResolveBuilderAdminLocale::class);

    $request = Request::create('/livewire/update', 'POST');
    $request->headers->set('referer', url('/admin/field-groups/1/edit?lang=de_CH'));

    $middleware->handle($request, fn (Request $request): Response => response('ok'));

    expect($request->input('lang'))->toBe('de_CH')
        ->and(app(BuilderLocaleResolver::class)->current())->toBe('de_CH');
});

it('does not restore locale from session on livewire subrequests for non-translatable resources', function (): void {
    session([BuilderLocaleResolver::ADMIN_SESSION_KEY => 'de_CH']);

    $middleware = app(ResolveBuilderAdminLocale::class);

    $request = Request::create('/livewire/update', 'POST');
    $request->headers->set('referer', url('/admin/items/1/edit'));

    $middleware->handle($request, fn (Request $request): Response => response('ok'));

    expect($request->input('lang'))->toBeNull();
});

it('resolves locale from session when request has no lang parameter on field groups', function (): void {
    session([BuilderLocaleResolver::ADMIN_SESSION_KEY => 'de_CH']);

    $request = Request::create('/admin/field-groups');

    app(ResolveBuilderAdminLocale::class)->handle($request, fn (Request $request): Response => response('ok'));

    expect(app(BuilderLocaleResolver::class)->current())->toBe('de_CH');
});
