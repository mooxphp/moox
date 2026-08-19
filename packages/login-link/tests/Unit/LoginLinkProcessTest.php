<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Moox\LoginLink\Database\Seeders\LoginLinkProcessSeeder;
use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Handlers\DumpRedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
        'ack' => AckRedemptionHandler::class,
        'dump' => DumpRedemptionHandler::class,
    ]);
    config()->set('login-link.templates', [
        'login' => 'login-link::mail.login-link',
        'ack' => 'login-link::mail.process-link',
        'dump' => 'login-link::mail.dump',
    ]);
});

it('seeds the login and ack process definitions', function (): void {
    (new LoginLinkProcessSeeder)->run();

    $login = LoginLinkProcess::query()->where('slug', 'login')->first();
    $ack = LoginLinkProcess::query()->where('slug', 'ack')->first();
    $demoDump = LoginLinkProcess::query()->where('slug', 'demo-dump')->first();
    $demoCampaign = LoginLinkProcess::query()->where('slug', 'demo-campaign')->first();

    expect($login)->not->toBeNull()
        ->and($login->title)->toBe('Login')
        ->and($login->handler_key)->toBe(RedemptionHandlerRegistry::DEFAULT_PROCESS)
        ->and($login->context)->toBe(LinkProcessContext::AUTH)
        ->and($login->template_key)->toBe('login')
        ->and($login->invalidate_prior)->toBeTrue()
        ->and($ack)->not->toBeNull()
        ->and($ack->title)->toBe('Acknowledge')
        ->and($ack->handler_key)->toBe('ack')
        ->and($ack->context)->toBe(LinkProcessContext::PUBLIC)
        ->and($ack->template_key)->toBe('ack')
        ->and($demoDump)->not->toBeNull()
        ->and($demoDump->handler_key)->toBe('dump')
        ->and($demoDump->invalidate_prior)->toBeTrue()
        ->and($demoCampaign)->not->toBeNull()
        ->and($demoCampaign->invalidate_prior)->toBeFalse();
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
        ->and($fresh->resolveExpiryMinutes())->toBe(30)
        ->and($fresh->resolveTemplateView())->toBe('login-link::mail.process-link');
});

it('rejects an unregistered handler key', function (): void {
    LoginLinkProcess::query()->create([
        'title' => 'Broken',
        'slug' => 'broken',
        'template_key' => 'login',
        'handler_key' => 'not-registered',
    ]);
})->throws(ValidationException::class);

it('rejects an unregistered template key', function (): void {
    LoginLinkProcess::query()->create([
        'title' => 'Broken template',
        'slug' => 'broken-template',
        'template_key' => 'missing-template',
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
