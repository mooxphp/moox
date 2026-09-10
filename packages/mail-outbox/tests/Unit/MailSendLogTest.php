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

test('delivery badge shows redirected, direct, or unknown', function (): void {
    $redirected = new MailSendLog([
        'intended_recipients' => ['customer@example.com'],
        'actual_recipients' => ['sandbox@example.com'],
        'status' => MailSendStatus::Suppressed,
    ]);
    $direct = new MailSendLog([
        'intended_recipients' => ['customer@example.com'],
        'actual_recipients' => ['customer@example.com'],
        'status' => MailSendStatus::Sent,
    ]);
    $unknown = new MailSendLog([
        'intended_recipients' => null,
        'actual_recipients' => ['customer@example.com'],
        'status' => MailSendStatus::Sent,
    ]);

    expect($redirected->deliveryBadgeLabel())->toBe(__('mail-outbox::fields.redirected'))
        ->and($redirected->deliveryBadgeColor())->toBe('warning')
        ->and($direct->deliveryBadgeLabel())->toBe(__('mail-outbox::fields.direct'))
        ->and($direct->deliveryBadgeColor())->toBe('success')
        ->and($unknown->deliveryBadgeLabel())->toBe('—')
        ->and($unknown->deliveryBadgeColor())->toBe('gray');
});

