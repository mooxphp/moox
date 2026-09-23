<?php

declare(strict_types=1);

use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Jobs\SendMailJob;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Tests\Support\EnvelopeRecipientMailable;
use Moox\MailOutbox\Tests\Support\RecordingTransport;
use Moox\MailOutbox\Tests\Support\TestMailable;
use Moox\MailOutbox\Tests\TestCase;

uses(TestCase::class);

require_once __DIR__.'/../Support/TestModeHelpers.php';

test('test mode off leaves normal sent behaviour unchanged', function (): void {
    config(['mail-outbox.test_mode.enabled' => false]);

    $transport = bindRecordingMailer('normal', new RecordingTransport);

    SendMailJob::dispatchSync((new TestMailable)->to('recipient@example.com'), 'normal');

    $log = MailSendLog::query()->first();

    expect($transport->sends)->toHaveCount(1)
        ->and($transport->sends[0]['recipients'])->toBe(['recipient@example.com'])
        ->and($log->status)->toBe(MailSendStatus::Sent)
        ->and($log->intended_recipients)->toBe(['recipient@example.com'])
        ->and($log->actual_recipients)->toBe(['recipient@example.com'])
        ->and($log->deliveredToIntendedRecipients())->toBeTrue();
});

test('non-allowlisted recipients are redirected and logged as suppressed', function (): void {
    enableTestMode();

    $transport = bindRecordingMailer('redirect', new RecordingTransport);

    SendMailJob::dispatchSync((new TestMailable)->to('customer@external.com'), 'redirect');

    $log = MailSendLog::query()->first();

    expect($transport->sends)->toHaveCount(1)
        ->and($transport->sends[0]['recipients'])->toBe(['sandbox@test.example'])
        ->and($transport->sends[0]['subject'])->toBe('[TEST to customer@external.com] Mail outbox test')
        ->and($log->status)->toBe(MailSendStatus::Suppressed)
        ->and($log->intended_recipients)->toBe(['customer@external.com'])
        ->and($log->actual_recipients)->toBe(['sandbox@test.example'])
        ->and($log->subject)->toBe('Mail outbox test')
        ->and($log->deliveredToIntendedRecipients())->toBeFalse()
        ->and($log->wasSuppressed())->toBeTrue();
});

test('allowlisted recipients are delivered for real while others are redirected in the same run', function (): void {
    enableTestMode(['allowlist' => ['*@allowlisted.com']]);

    $transport = bindRecordingMailer('mixed', new RecordingTransport);

    $mailable = (new TestMailable)
        ->to('customer@external.com')
        ->cc('qa@allowlisted.com');

    SendMailJob::dispatchSync($mailable, 'mixed');

    $log = MailSendLog::query()->first();

    expect($transport->sends)->toHaveCount(2)
        ->and($transport->sends[0]['recipients'])->toBe(['qa@allowlisted.com'])
        ->and($transport->sends[0]['subject'])->toBe('Mail outbox test')
        ->and($transport->sends[1]['recipients'])->toBe(['sandbox@test.example'])
        ->and($transport->sends[1]['subject'])->toBe('[TEST to customer@external.com] Mail outbox test')
        ->and($log->status)->toBe(MailSendStatus::Suppressed)
        ->and($log->intended_recipients)->toContain('customer@external.com', 'qa@allowlisted.com')
        ->and($log->actual_recipients)->toContain('qa@allowlisted.com', 'sandbox@test.example')
        ->and($log->deliveredToIntendedRecipients())->toBeFalse();
});

test('all allowlisted recipients are sent for real with sent status', function (): void {
    enableTestMode(['allowlist' => ['*@allowlisted.com']]);

    $transport = bindRecordingMailer('allowlisted', new RecordingTransport);

    SendMailJob::dispatchSync((new TestMailable)->to('qa@allowlisted.com'), 'allowlisted');

    $log = MailSendLog::query()->first();

    expect($transport->sends)->toHaveCount(1)
        ->and($transport->sends[0]['recipients'])->toBe(['qa@allowlisted.com'])
        ->and($log->status)->toBe(MailSendStatus::Sent)
        ->and($log->deliveredToIntendedRecipients())->toBeTrue();
});

test('a domain object relying on deliveredToIntendedRecipients does not mark delivery when suppressed', function (): void {
    enableTestMode();

    bindRecordingMailer('domain', new RecordingTransport);

    SendMailJob::dispatchSync((new TestMailable)->to('customer@external.com'), 'domain');

    $log = MailSendLog::query()->firstOrFail();
    $markedDelivered = false;

    if ($log->deliveredToIntendedRecipients()) {
        $markedDelivered = true;
    }

    expect($markedDelivered)->toBeFalse()
        ->and($log->status)->toBe(MailSendStatus::Suppressed);
});

test('turning test mode off restores normal behaviour on subsequent sends', function (): void {
    enableTestMode();
    bindRecordingMailer('toggle', new RecordingTransport);

    SendMailJob::dispatchSync((new TestMailable)->to('first@external.com'), 'toggle');

    config(['mail-outbox.test_mode.enabled' => false]);

    SendMailJob::dispatchSync((new TestMailable)->to('second@example.com'), 'toggle');

    $logs = MailSendLog::query()->orderBy('id')->get();

    expect($logs)->toHaveCount(2)
        ->and($logs[0]->status)->toBe(MailSendStatus::Suppressed)
        ->and($logs[1]->status)->toBe(MailSendStatus::Sent)
        ->and($logs[1]->actual_recipients)->toBe(['second@example.com']);
});

test('alwaysTo does not leak to a subsequent send after purge', function (): void {
    enableTestMode();
    $transport = bindRecordingMailer('purge', new RecordingTransport);

    SendMailJob::dispatchSync((new TestMailable)->to('customer@external.com'), 'purge');

    config(['mail-outbox.test_mode.enabled' => false]);

    SendMailJob::dispatchSync((new TestMailable)->to('real@example.com'), 'purge');

    expect($transport->sends[1]['recipients'])->toBe(['real@example.com']);
});

test('envelope-defined recipients honour allowlist on the real-delivery leg', function (): void {
    enableTestMode(['allowlist' => ['*@allowlisted.com']]);

    $transport = bindRecordingMailer('envelope', new RecordingTransport);

    $mailable = new EnvelopeRecipientMailable(
        toAddresses: ['customer@external.com'],
        ccAddresses: ['qa@allowlisted.com'],
    );

    SendMailJob::dispatchSync($mailable, 'envelope');

    expect($transport->sends)->toHaveCount(2)
        ->and($transport->sends[0]['recipients'])->toBe(['qa@allowlisted.com'])
        ->and($transport->sends[1]['recipients'])->toBe(['sandbox@test.example']);
});

test('suppressed send does not get overwritten by failed hook', function (): void {
    enableTestMode();
    bindRecordingMailer('failed-hook', new RecordingTransport);

    $job = new SendMailJob((new TestMailable)->to('customer@external.com'), 'failed-hook');
    SendMailJob::dispatchSync($job->mailable, $job->mailer);

    $log = MailSendLog::query()->firstOrFail();
    $logId = $log->id;

    $job->mailSendLogId = $logId;
    $job->failed(new RuntimeException('late failure'));

    expect(MailSendLog::query()->find($logId)?->status)->toBe(MailSendStatus::Suppressed);
});
