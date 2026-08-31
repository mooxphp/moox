<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Moox\LoginLink\Events\ProcessLinkAcknowledged;
use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Mail\ProcessLinkMail;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\LoginLinkRedemptionService;
use Moox\LoginLink\Services\LoginLinkService;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\LoginLink\Tests\Support\TestSubject;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Mail::fake();
    Event::fake([ProcessLinkAcknowledged::class]);

    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
        'ack' => AckRedemptionHandler::class,
    ]);
    config()->set('login-link.templates', [
        'login' => 'login-link::mail.login-link',
        'ack' => 'login-link::mail.process-link',
    ]);
    config()->set('login-link.ack.redirect_url', '/ack-ok');
    config()->set('login-link.expiration_minutes', 60);

    $this->app['db']->connection()->getSchemaBuilder()->create('test_subjects', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });
});

it('runs the non-login ack flow end-to-end for a public non-user subject', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Delivery address',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Acknowledge delivery',
        'slug' => 'confirm-delivery',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'ack',
        'template_key' => 'ack',
        'expiry_minutes' => 20,
        'mail_from' => 'links@example.com',
    ]);

    $link = app(LoginLinkService::class)->issue(
        'confirm-delivery',
        $subject,
        'ap@example.com',
        null,
        Request::create('/', 'POST'),
        payload: ['source' => 'portal'],
    );

    expect($link->process)->toBe('confirm-delivery')
        ->and($link->subject_type)->toBe(TestSubject::class)
        ->and($link->subject_id)->toBe($subject->id)
        ->and($link->user_id)->toBeNull()
        ->and($link->panel_id)->toBeNull()
        ->and($link->payload)->toBe(['source' => 'portal'])
        ->and($link->used_at)->toBeNull();

    Mail::assertQueued(ProcessLinkMail::class, function (ProcessLinkMail $mail) use ($link): bool {
        return $mail->loginLink->is($link)
            && $mail->process?->slug === 'confirm-delivery'
            && $mail->process?->template_key === 'ack'
            && $mail->process?->context === LinkProcessContext::PUBLIC;
    });

    $result = app(LoginLinkRedemptionService::class)->redeem($link->getKey(), null);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())->toEndWith('/ack-ok')
        ->and($link->fresh()->used_at)->not->toBeNull()
        ->and(Auth::guard('web')->check())->toBeFalse();

    Event::assertDispatched(ProcessLinkAcknowledged::class, function (ProcessLinkAcknowledged $event) use ($link, $subject): bool {
        return $event->loginLink->is($link)
            && $event->subject->is($subject)
            && $event->panelId === null;
    });

    expect(app(LoginLinkRedemptionService::class)->redeem($link->getKey(), null))->toBeNull();
});

it('rejects public links when redeemed via a panel context', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Address',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Verify',
        'slug' => 'verify-address',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'ack',
        'template_key' => 'ack',
    ]);

    $link = LoginLink::query()->create([
        'panel_id' => null,
        'process' => 'verify-address',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'ap@example.com',
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    expect(app(LoginLinkRedemptionService::class)->redeem($link->getKey(), 'admin'))->toBeNull()
        ->and($link->fresh()->used_at)->toBeNull()
        ->and(Auth::guard('web')->check())->toBeFalse();
});

it('resolves the handler via the process definition handler_key when slug differs', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Address',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Verify',
        'slug' => 'verify-address',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'ack',
        'template_key' => 'ack',
    ]);

    $link = LoginLink::query()->create([
        'panel_id' => null,
        'process' => 'verify-address',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'ap@example.com',
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $result = app(LoginLinkRedemptionService::class)->redeem($link->getKey(), null);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())->toEndWith('/ack-ok')
        ->and(Auth::guard('web')->check())->toBeFalse();

    Event::assertDispatched(ProcessLinkAcknowledged::class);
});

it('renders the unavailable page when a public link was already used', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Address',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Verify',
        'slug' => 'verify-address',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'ack',
        'template_key' => 'ack',
    ]);

    $link = LoginLink::query()->create([
        'panel_id' => null,
        'process' => 'verify-address',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'ap@example.com',
        'expires_at' => now()->addHour(),
        'used_at' => now(),
    ]);

    config()->set('login-link.public_support', [
        'name' => 'Acme Support',
        'email' => 'help@example.com',
        'phone' => '+49 1234',
    ]);

    $url = URL::temporarySignedRoute(
        'login-link.public.consume',
        $link->expires_at,
        ['loginLink' => $link->getKey()],
    );

    $this->get($url)
        ->assertOk()
        ->assertSee(__('login-link::translations.public_used_title'), false)
        ->assertSee('help@example.com', false)
        ->assertSee('+49 1234', false);
});

it('renders the unavailable page when the signed public URL has expired', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Address',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Verify',
        'slug' => 'verify-address',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'ack',
        'template_key' => 'ack',
    ]);

    $link = LoginLink::query()->create([
        'panel_id' => null,
        'process' => 'verify-address',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'ap@example.com',
        'expires_at' => now()->subMinute(),
        'used_at' => null,
    ]);

    $url = URL::temporarySignedRoute(
        'login-link.public.consume',
        now()->subMinute(),
        ['loginLink' => $link->getKey()],
    );

    $this->get($url)
        ->assertOk()
        ->assertSee(__('login-link::translations.public_expired_title'), false);
});
