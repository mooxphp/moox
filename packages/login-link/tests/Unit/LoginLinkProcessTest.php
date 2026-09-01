<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Moox\LoginLink\Database\Seeders\LoginLinkProcessSeeder;
use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Handlers\MassMailRedemptionHandler;
use Moox\LoginLink\Handlers\VerifyEmailRedemptionHandler;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
        'verify-email' => VerifyEmailRedemptionHandler::class,
        'mass-mail' => MassMailRedemptionHandler::class,
        'ack' => AckRedemptionHandler::class,
    ]);
});

it('seeds the login, email verification, and mass-mail process definitions', function (): void {
    (new LoginLinkProcessSeeder)->run();

    $login = LoginLinkProcess::query()->where('slug', 'login')->first();
    $verifyEmail = LoginLinkProcess::query()->where('slug', 'verify-email')->first();
    $massMail = LoginLinkProcess::query()->where('slug', 'mass-mail')->first();

    expect($login)->not->toBeNull()
        ->and($login->title)->toBe('Passwordless login')
        ->and($login->handler_key)->toBe(RedemptionHandlerRegistry::DEFAULT_PROCESS)
        ->and($login->context)->toBe(LinkProcessContext::AUTH)
        ->and($login->template_key)->toBe('login')
        ->and($login->invalidate_prior)->toBeTrue()
        ->and($verifyEmail)->not->toBeNull()
        ->and($verifyEmail->title)->toBe('Email verification')
        ->and($verifyEmail->handler_key)->toBe('verify-email')
        ->and($verifyEmail->context)->toBe(LinkProcessContext::PUBLIC)
        ->and($verifyEmail->template_key)->toBe('verify-email')
        ->and($verifyEmail->invalidate_prior)->toBeTrue()
        ->and($massMail)->not->toBeNull()
        ->and($massMail->title)->toBe('Mass mail verification')
        ->and($massMail->handler_key)->toBe('mass-mail')
        ->and($massMail->invalidate_prior)->toBeFalse()
        ->and(LoginLinkProcess::query()->whereIn('slug', ['ack', 'demo-dump', 'demo-campaign'])->exists())->toBeFalse();
});

it('persists title slug mail_from template and context', function (): void {
    $process = LoginLinkProcess::query()->create([
        'title' => 'Verify address',
        'slug' => 'verify-address',
        'context' => LinkProcessContext::PUBLIC,
        'mail_from' => 'noreply@example.com',
        'content' => 'Please confirm your address.',
        'template_key' => 'ack',
        'handler_key' => 'ack',
        'expiry_minutes' => 30,
        'invalidate_prior' => false,
    ]);

    $fresh = $process->fresh();

    expect($fresh->title)->toBe('Verify address')
        ->and($fresh->slug)->toBe('verify-address')
        ->and($fresh->mail_from)->toBe('noreply@example.com')
        ->and($fresh->content)->toBe('Please confirm your address.')
        ->and($fresh->template_key)->toBe('ack')
        ->and($fresh->context)->toBe(LinkProcessContext::PUBLIC)
        ->and($fresh->invalidate_prior)->toBeFalse()
        ->and($fresh->resolveExpiryMinutes())->toBe(30);
});

it('rejects an unregistered handler key', function (): void {
    LoginLinkProcess::query()->create([
        'title' => 'Broken',
        'slug' => 'broken',
        'template_key' => 'login',
        'handler_key' => 'not-registered',
    ]);
})->throws(ValidationException::class);

it('rejects an empty template key', function (): void {
    LoginLinkProcess::query()->create([
        'title' => 'Broken template',
        'slug' => 'broken-template',
        'template_key' => '',
        'handler_key' => 'login',
    ]);
})->throws(ValidationException::class);

it('uses the package default expiry when expiry_minutes is null', function (): void {
    config()->set('login-link.expiration_minutes', 45);

    $process = LoginLinkProcess::query()->create([
        'title' => 'Login',
        'slug' => 'login-default-expiry',
        'template_key' => 'login',
        'handler_key' => 'login',
        'expiry_minutes' => null,
    ]);

    expect($process->resolveExpiryMinutes())->toBe(45);
});
