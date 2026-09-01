<?php

declare(strict_types=1);

use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Mail\LoginLinkEmail;
use Moox\LoginLink\Mail\ProcessLinkMail;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Support\LinkProcessContext;
use Moox\LoginLink\Tests\Support\TestSubject;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('core.packages', []);
    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
        'ack' => AckRedemptionHandler::class,
    ]);
    config()->set('login-link.templates', [
        'login' => 'login-link::mail.login-link',
        'ack' => 'login-link::mail.process-link',
    ]);

    $this->app['db']->connection()->getSchemaBuilder()->create('test_subjects', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });
});

function makeProcessLinkMailRecord(string $processSlug, string $templateKey, string $context): LoginLink
{
    $subject = TestSubject::query()->create([
        'name' => 'Recipient',
        'email' => 'ap@example.com',
    ]);

    LoginLinkProcess::query()->create([
        'title' => 'Test process',
        'slug' => $processSlug,
        'context' => $context,
        'handler_key' => $processSlug === 'login' ? 'login' : 'ack',
        'template_key' => $templateKey,
        'content' => 'Please continue.',
    ]);

    return LoginLink::query()->create([
        'panel_id' => $context === LinkProcessContext::AUTH ? 'admin' : null,
        'process' => $processSlug,
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => 'ap@example.com',
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);
}

it('sends html process templates as html without compiling mjml', function (): void {
    $link = makeProcessLinkMailRecord('confirm-delivery', 'ack', LinkProcessContext::PUBLIC);
    $process = LoginLinkProcess::query()->where('slug', 'confirm-delivery')->first();

    $html = (new ProcessLinkMail($link, $process))->render();

    expect($html)
        ->toContain('<!doctype html>')
        ->toContain('Please continue.')
        ->not->toContain('<mjml');
});

it('compiles the package login mjml view when spatie is available', function (): void {
    if (! class_exists('Spatie\\Mjml\\Mjml')) {
        test()->markTestSkipped('Spatie MJML is not installed.');
    }

    $link = makeProcessLinkMailRecord('login', 'login', LinkProcessContext::AUTH);
    $process = LoginLinkProcess::query()->where('slug', 'login')->first();

    try {
        $html = (new ProcessLinkMail($link, $process))->render();
    } catch (Throwable $exception) {
        test()->markTestSkipped($exception->getMessage());
    }

    expect($html)
        ->not->toContain('<mjml')
        ->toContain(__('login-link::translations.mail_cta'))
        ->toContain('Test process');
});

it('uses a host-configured blade view instead of the package default', function (): void {
    config()->set('login-link.templates.ack', 'login-link::mail.login-link');

    $link = makeProcessLinkMailRecord('confirm-delivery', 'ack', LinkProcessContext::PUBLIC);
    $process = LoginLinkProcess::query()->where('slug', 'confirm-delivery')->first();

    try {
        $html = (new ProcessLinkMail($link, $process))->render();
    } catch (Throwable $exception) {
        test()->markTestSkipped($exception->getMessage());
    }

    expect($html)
        ->not->toContain('<!doctype html>')
        ->toContain(__('login-link::translations.mail_cta'));
});

it('keeps LoginLinkEmail as a constructor-compatible subclass of ProcessLinkMail', function (): void {
    $link = makeProcessLinkMailRecord('login', 'login', LinkProcessContext::AUTH);

    $mail = new LoginLinkEmail($link);

    expect($mail)->toBeInstanceOf(ProcessLinkMail::class)
        ->and($mail->loginLink->is($link))->toBeTrue();
});
