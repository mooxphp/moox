<?php

declare(strict_types=1);

use Moox\LoginLink\Handlers\AckRedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
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

it('sends the packaged html demo when no mail-template row matches', function (): void {
    $link = makeProcessLinkMailRecord('confirm-delivery', 'ack', LinkProcessContext::PUBLIC);
    $process = LoginLinkProcess::query()->where('slug', 'confirm-delivery')->first();

    $html = (new ProcessLinkMail($link, $process))->render();

    expect($html)
        ->toContain('<!doctype html>')
        ->toContain('Please continue.')
        ->toContain(__('login-link::translations.mail_cta'))
        ->not->toContain('<mjml');
});
