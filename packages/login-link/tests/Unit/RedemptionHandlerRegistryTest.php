<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Moox\LoginLink\Contracts\RedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

it('registers login as the built-in first handler', function (): void {
    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
    ]);

    $registry = app(RedemptionHandlerRegistry::class);

    expect($registry->has('login'))->toBeTrue()
        ->and($registry->resolve('login'))->toBe(LoginRedemptionHandler::class)
        ->and($registry->get('login'))->toBeInstanceOf(LoginRedemptionHandler::class)
        ->and($registry->get('login'))->toBeInstanceOf(RedemptionHandler::class);
});

it('merges handlers contributed by other packages', function (): void {
    $contributor = new class implements RedemptionHandler
    {
        public function handle(LoginLink $loginLink, ?string $panelId): ?RedirectResponse
        {
            return null;
        }
    };

    config()->set('core.packages', [
        'delivery' => ['package' => 'Moox Delivery'],
    ]);
    config()->set('delivery.login-link.handlers', [
        'verify-address' => $contributor::class,
    ]);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
    ]);

    $registry = app(RedemptionHandlerRegistry::class);

    expect($registry->all())->toMatchArray([
        'verify-address' => $contributor::class,
        'login' => LoginRedemptionHandler::class,
    ]);
});

it('lets login-link.handlers override package contributions', function (): void {
    $packageHandler = new class implements RedemptionHandler
    {
        public function handle(LoginLink $loginLink, ?string $panelId): ?RedirectResponse
        {
            return null;
        }
    };

    $overrideHandler = new class implements RedemptionHandler
    {
        public function handle(LoginLink $loginLink, ?string $panelId): ?RedirectResponse
        {
            return redirect('/');
        }
    };

    config()->set('core.packages', [
        'delivery' => ['package' => 'Moox Delivery'],
    ]);
    config()->set('delivery.login-link.handlers', [
        'verify-address' => $packageHandler::class,
    ]);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
        'verify-address' => $overrideHandler::class,
    ]);

    expect(app(RedemptionHandlerRegistry::class)->resolve('verify-address'))
        ->toBe($overrideHandler::class);
});

it('returns null for an unregistered handler key', function (): void {
    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
    ]);

    $registry = app(RedemptionHandlerRegistry::class);

    expect($registry->has('missing'))->toBeFalse()
        ->and($registry->resolve('missing'))->toBeNull()
        ->and($registry->get('missing'))->toBeNull();
});
