<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Moox\LoginLink\Events\ProcessLinkAcknowledged;
use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Mail\ProcessLinkMail;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\LoginLinkRedemptionService;
use Moox\LoginLink\Services\LoginLinkService;
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
    config()->set('login-link.ack.redirect_url', '/ack-ok');
    config()->set('login-link.expiration_minutes', 60);

    $this->app['db']->connection()->getSchemaBuilder()->create('test_subjects', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });
});

it('runs the non-login ack flow end-to-end for a non-user subject', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Delivery address',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Acknowledge delivery',
        'slug' => 'confirm-delivery',
        'handler_key' => 'ack',
        'expiry_minutes' => 20,
        'mail_from' => 'links@example.com',
        'content' => 'Please confirm this delivery address.',
    ]);

    $link = app(LoginLinkService::class)->issue(
        'confirm-delivery',
        $subject,
        'ap@example.com',
        'admin',
        Request::create('/', 'POST'),
    );

    expect($link->process)->toBe('confirm-delivery')
        ->and($link->subject_type)->toBe(TestSubject::class)
        ->and($link->subject_id)->toBe($subject->id)
        ->and($link->user_id)->toBeNull()
        ->and($link->used_at)->toBeNull();

    Mail::assertQueued(ProcessLinkMail::class, function (ProcessLinkMail $mail) use ($link): bool {
        return $mail->loginLink->is($link)
            && $mail->process?->slug === 'confirm-delivery'
            && $mail->process?->mail_from === 'links@example.com'
            && $mail->process?->content === 'Please confirm this delivery address.';
    });

    $result = app(LoginLinkRedemptionService::class)->redeem($link->getKey(), 'admin');

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())->toEndWith('/ack-ok')
        ->and($link->fresh()->used_at)->not->toBeNull()
        ->and(Auth::guard('web')->check())->toBeFalse();

    Event::assertDispatched(ProcessLinkAcknowledged::class, function (ProcessLinkAcknowledged $event) use ($link, $subject): bool {
        return $event->loginLink->is($link)
            && $event->subject->is($subject)
            && $event->panelId === 'admin';
    });

    expect(app(LoginLinkRedemptionService::class)->redeem($link->getKey(), 'admin'))->toBeNull();
});

it('resolves the handler via the process definition handler_key when slug differs', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Address',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Verify',
        'slug' => 'verify-address',
        'handler_key' => 'ack',
        'content' => 'Verify me.',
    ]);

    $link = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => 'verify-address',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'ap@example.com',
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $result = app(LoginLinkRedemptionService::class)->redeem($link->getKey(), 'admin');

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())->toEndWith('/ack-ok')
        ->and(Auth::guard('web')->check())->toBeFalse();

    Event::assertDispatched(ProcessLinkAcknowledged::class);
});
