<?php

declare(strict_types=1);

use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Tests\TestCase;

uses(TestCase::class);

test('redirected sends are detected when intended and actual recipients differ', function (): void {
    $log = new MailSendLog([
        'intended_recipients' => ['customer@example.com'],
        'actual_recipients' => ['sandbox@example.com'],
        'status' => MailSendStatus::Suppressed,
    ]);

    expect($log->isRedirected())->toBeTrue()
        ->and($log->primaryRecipientLabel())->toBe('sandbox@example.com');
});

test('direct sends are not marked redirected when recipient sets match', function (): void {
    $log = new MailSendLog([
        'intended_recipients' => ['customer@example.com'],
        'actual_recipients' => ['customer@example.com'],
        'status' => MailSendStatus::Sent,
    ]);

    expect($log->isRedirected())->toBeFalse();
});

test('sends are not marked redirected when intended recipients are unknown', function (): void {
    $log = new MailSendLog([
        'intended_recipients' => null,
        'actual_recipients' => ['customer@example.com'],
        'status' => MailSendStatus::Sent,
    ]);

    expect($log->isRedirected())->toBeFalse();
});

test('sends are not marked redirected when intended recipients are an empty list', function (): void {
    $log = new MailSendLog([
        'intended_recipients' => [],
        'actual_recipients' => ['customer@example.com'],
        'status' => MailSendStatus::Sent,
    ]);

    expect($log->isRedirected())->toBeFalse();
});

