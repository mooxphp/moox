<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Moox\LoginLink\Handlers\DumpRedemptionHandler;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\LoginLink\Tests\Support\TestSubject;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('login-link.handlers', [
        'dump' => DumpRedemptionHandler::class,
    ]);
    config()->set('login-link.templates', [
        'dump' => 'login-link::mail.dump',
    ]);

    $this->app['db']->connection()->getSchemaBuilder()->create('test_subjects', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });
});

it('dumps redeem context into the session without authenticating', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Demo subject',
        'email' => 'demo@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Demo dump',
        'slug' => 'demo-dump',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'dump',
        'template_key' => 'dump',
        'invalidate_prior' => true,
    ]);

    $link = LoginLink::query()->create([
        'panel_id' => null,
        'process' => 'demo-dump',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'demo@example.com',
        'payload' => ['campaign' => 'spring'],
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $result = app(DumpRedemptionHandler::class)->handle($link, null);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())->toContain('/login-link/demo/dump')
        ->and(Auth::guard('web')->check())->toBeFalse()
        ->and(session(DumpRedemptionHandler::SESSION_KEY)['login_link']['payload'])->toBe(['campaign' => 'spring'])
        ->and(session(DumpRedemptionHandler::SESSION_KEY)['subject']['id'])->toBe($subject->id)
        ->and(session(DumpRedemptionHandler::SESSION_KEY)['auth_check'])->toBeFalse();
});
