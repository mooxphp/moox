<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Mail\ProcessLinkMail;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\LoginLinkService;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\LoginLink\Tests\Support\TestSubject;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Mail::fake();

    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'ack' => AckRedemptionHandler::class,
    ]);

    $this->app['db']->connection()->getSchemaBuilder()->create('test_subjects', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });

    LoginLinkProcess::query()->create([
        'title' => 'Verify',
        'slug' => 'verify-address',
        'context' => LinkProcessContext::PUBLIC,
        'handler_key' => 'ack',
        'template_key' => 'ack',
    ]);
});

it('issues the link without sending mail when the gate is closed', function (): void {
    config()->set('login-link.mail.enabled', false);

    $subject = TestSubject::query()->create([
        'name' => 'Address',
        'email' => 'ap@example.com',
    ]);

    $link = app(LoginLinkService::class)->issue(
        'verify-address',
        $subject,
        'ap@example.com',
        null,
        Request::create('/', 'POST'),
    );

    expect($link->exists)->toBeTrue()
        ->and($link->email)->toBe('ap@example.com');

    Mail::assertNothingQueued();
    Mail::assertNothingSent();
});

it('queues mail on the application mailer when the gate is open', function (): void {
    config()->set('login-link.mail.enabled', true);

    $subject = TestSubject::query()->create([
        'name' => 'Address',
        'email' => 'ap@example.com',
    ]);

    app(LoginLinkService::class)->issue(
        'verify-address',
        $subject,
        'ap@example.com',
        null,
        Request::create('/', 'POST'),
    );

    Mail::assertQueued(ProcessLinkMail::class, function (ProcessLinkMail $mail): bool {
        return $mail->mailer === config('mail.default')
            && $mail->hasTo('ap@example.com');
    });
});
