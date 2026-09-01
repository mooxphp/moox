<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Mail\ProcessLinkMail;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\LoginLinkService;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\LoginLink\Tests\Support\TestSubject;
use Moox\LoginLink\Tests\Support\TestUser;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Mail::fake();

    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
        'ack' => AckRedemptionHandler::class,
    ]);
    config()->set('login-link.expiration_minutes', 60);

    $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    $this->app['db']->connection()->getSchemaBuilder()->create('test_subjects', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });
});

it('invalidates prior valid links for the same process and subject when policy enabled', function (): void {
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
        'expiry_minutes' => 15,
        'mail_from' => 'links@example.com',
        'invalidate_prior' => true,
    ]);

    $service = app(LoginLinkService::class);
    $request = Request::create('/', 'POST', server: ['REMOTE_ADDR' => '203.0.113.10']);

    $first = $service->issue('verify-address', $subject, 'ap@example.com', null, $request);
    $second = $service->issue('verify-address', $subject, 'ap@example.com', null, $request);

    expect($first->fresh()->used_at)->not->toBeNull()
        ->and($second->fresh()->used_at)->toBeNull()
        ->and(LoginLink::query()->where('process', 'verify-address')->whereNull('used_at')->count())->toBe(1);
});

it('keeps prior valid links when invalidate_prior is disabled', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Address',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Campaign',
        'slug' => 'campaign',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'ack',
        'template_key' => 'ack',
        'invalidate_prior' => false,
    ]);

    $service = app(LoginLinkService::class);
    $request = Request::create('/', 'POST');

    $first = $service->issue('campaign', $subject, 'ap@example.com', null, $request);
    $second = $service->issue('campaign', $subject, 'ap@example.com', null, $request);

    expect($first->fresh()->used_at)->toBeNull()
        ->and($second->fresh()->used_at)->toBeNull()
        ->and(LoginLink::query()->where('process', 'campaign')->whereNull('used_at')->count())->toBe(2);
});

it('uses expiry and from from the process definition', function (): void {
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
        'expiry_minutes' => 15,
        'mail_from' => 'links@example.com',
        'content' => 'Please verify.',
    ]);

    $link = app(LoginLinkService::class)->issue(
        'verify-address',
        $subject,
        'ap@example.com',
        null,
        Request::create('/', 'POST'),
    );

    expect($link->expires_at->between(
        now()->addMinutes(14),
        now()->addMinutes(16),
    ))->toBeTrue()
        ->and($link->panel_id)->toBeNull();

    Mail::assertQueued(ProcessLinkMail::class, function (ProcessLinkMail $mail): bool {
        return $mail->process?->mail_from === 'links@example.com'
            && $mail->process?->template_key === 'ack'
            && $mail->process?->content === 'Please verify.';
    });
});

it('stores payload on the link instance', function (): void {
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

    $link = app(LoginLinkService::class)->issue(
        'verify-address',
        $subject,
        'ap@example.com',
        null,
        Request::create('/', 'POST'),
        payload: ['campaign' => 'spring', 'ref' => 42],
    );

    expect($link->payload)->toBe(['campaign' => 'spring', 'ref' => 42]);
});

it('resends by invalidating the current link and issuing a new one', function (): void {
    $user = TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => bcrypt('secret'),
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Login',
        'slug' => 'login',
        'context' => LinkProcessContext::AUTH,
        'handler_key' => 'login',
        'template_key' => 'login',
        'invalidate_prior' => true,
    ]);

    $service = app(LoginLinkService::class);
    $request = Request::create('/', 'POST');

    $first = $service->issue('login', $user, $user->email, 'admin', $request, setUserMorph: true);
    $second = $service->resendLink($first, $request);

    expect($second)->not->toBeNull()
        ->and($first->fresh()->used_at)->not->toBeNull()
        ->and($second->fresh()->used_at)->toBeNull()
        ->and($second->is($first))->toBeFalse();

    Mail::assertQueued(ProcessLinkMail::class, 2);
});

it('falls back to package default expiry when process expiry is unset', function (): void {
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
        'expiry_minutes' => null,
    ]);

    config()->set('login-link.expiration_minutes', 45);

    $link = app(LoginLinkService::class)->issue(
        'verify-address',
        $subject,
        'ap@example.com',
        null,
        Request::create('/', 'POST'),
    );

    expect($link->expires_at->between(
        now()->addMinutes(44),
        now()->addMinutes(46),
    ))->toBeTrue();
});
