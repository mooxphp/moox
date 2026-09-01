<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Moox\LoginLink\Events\ProcessLinkAcknowledged;
use Moox\LoginLink\Handlers\MassMailRedemptionHandler;
use Moox\LoginLink\Handlers\VerifyEmailRedemptionHandler;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\LoginLink\Tests\Support\TestSubject;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Event::fake([ProcessLinkAcknowledged::class]);

    config()->set('login-link.handlers', [
        'verify-email' => VerifyEmailRedemptionHandler::class,
        'mass-mail' => MassMailRedemptionHandler::class,
    ]);

    $this->app['db']->connection()->getSchemaBuilder()->create('test_subjects', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });
});

it('verifies email without authenticating and lands on the example page', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Mailbox owner',
        'email' => 'owner@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Email verification',
        'slug' => 'verify-email',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'verify-email',
        'template_key' => 'verify-email',
        'invalidate_prior' => true,
    ]);

    $link = LoginLink::query()->create([
        'panel_id' => null,
        'process' => 'verify-email',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'owner@example.com',
        'payload' => ['purpose' => 'email-verification'],
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $result = app(VerifyEmailRedemptionHandler::class)->handle($link, null);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())->toContain('/login-link/examples/email-verified')
        ->and(Auth::guard('web')->check())->toBeFalse()
        ->and(session(VerifyEmailRedemptionHandler::SESSION_KEY)['email'])->toBe('owner@example.com')
        ->and(session(VerifyEmailRedemptionHandler::SESSION_KEY)['process_slug'])->toBe('verify-email');

    Event::assertDispatched(ProcessLinkAcknowledged::class);
});

it('confirms a mass mailing without authenticating or requiring a panel', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Recipient',
        'email' => 'reader@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Mass mail verification',
        'slug' => 'mass-mail',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'mass-mail',
        'template_key' => 'mass-mail',
        'invalidate_prior' => false,
    ]);

    $link = LoginLink::query()->create([
        'panel_id' => null,
        'process' => 'mass-mail',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'reader@example.com',
        'payload' => ['campaign' => 'Spring newsletter', 'mailing_id' => 'demo-001'],
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $result = app(MassMailRedemptionHandler::class)->handle($link, null);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())->toContain('/login-link/examples/mailing-confirmed')
        ->and(Auth::guard('web')->check())->toBeFalse()
        ->and(session(MassMailRedemptionHandler::SESSION_KEY)['payload']['campaign'])->toBe('Spring newsletter');

    Event::assertDispatched(ProcessLinkAcknowledged::class);
});
