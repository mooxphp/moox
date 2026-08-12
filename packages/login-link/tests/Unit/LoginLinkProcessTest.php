<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Moox\LoginLink\Database\Seeders\LoginLinkProcessSeeder;
use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
        'ack' => AckRedemptionHandler::class,
    ]);
});

it('seeds the login and ack process definitions', function (): void {
    (new LoginLinkProcessSeeder)->run();

    $login = LoginLinkProcess::query()->where('slug', 'login')->first();
    $ack = LoginLinkProcess::query()->where('slug', 'ack')->first();

    expect($login)->not->toBeNull()
        ->and($login->title)->toBe('Login')
        ->and($login->handler_key)->toBe(RedemptionHandlerRegistry::DEFAULT_PROCESS)
        ->and($login->mail_from)->toBeNull()
        ->and($login->content)->toBeNull()
        ->and($ack)->not->toBeNull()
        ->and($ack->title)->toBe('Acknowledge')
        ->and($ack->handler_key)->toBe('ack')
        ->and($ack->content)->toBe('Click the button below to confirm.');
});

it('persists title slug mail_from and content', function (): void {
    $process = LoginLinkProcess::query()->create([
        'title' => 'Verify address',
        'slug' => 'verify-address',
        'mail_from' => 'noreply@example.com',
        'content' => 'Please confirm your address.',
        'handler_key' => 'login',
        'expiry_minutes' => 30,
    ]);

    $fresh = $process->fresh();

    expect($fresh->title)->toBe('Verify address')
        ->and($fresh->slug)->toBe('verify-address')
        ->and($fresh->mail_from)->toBe('noreply@example.com')
        ->and($fresh->content)->toBe('Please confirm your address.')
        ->and($fresh->resolveExpiryMinutes())->toBe(30);
});

it('rejects an unregistered handler key', function (): void {
    LoginLinkProcess::query()->create([
        'title' => 'Broken',
        'slug' => 'broken',
        'handler_key' => 'not-registered',
    ]);
})->throws(ValidationException::class);

it('uses the package default expiry when expiry_minutes is null', function (): void {
    config()->set('login-link.expiration_minutes', 45);

    $process = LoginLinkProcess::query()->create([
        'title' => 'Login',
        'slug' => 'login-default-expiry',
        'handler_key' => 'login',
        'expiry_minutes' => null,
    ]);

    expect($process->resolveExpiryMinutes())->toBe(45);
});
