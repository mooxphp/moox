<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Moox\MailOutbox\Contracts\ProviderMessageIdReader;
use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Jobs\SendMailJob;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Support\SymfonySentMessageProviderIdReader;
use Moox\MailOutbox\Tests\Support\TestMailable;
use Moox\MailOutbox\Tests\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

uses(TestCase::class);

test('successful send stores self-assigned correlation id on the log', function (): void {
    Mail::fake();

    SendMailJob::dispatchSync((new TestMailable)->to('c@example.com'), 'array');

    $log = MailSendLog::query()->first();

    expect($log->correlation_id)->not->toBeEmpty()
        ->and($log->status)->toBe(MailSendStatus::Sent);
});

test('successful send puts correlation id on the outbound message header', function (): void {
    config(['mail-outbox.correlation_header' => 'X-Moox-Mail-Correlation-Id']);

    $captured = new class
    {
        public ?string $headerValue = null;
    };

    $transport = new class($captured) implements TransportInterface
    {
        public function __construct(private object $captured)
        {
        }

        public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
        {
            if ($message instanceof Email && $message->getHeaders()->has('X-Moox-Mail-Correlation-Id')) {
                $this->captured->headerValue = $message->getHeaders()
                    ->get('X-Moox-Mail-Correlation-Id')
                    ?->getBodyAsString();
            }

            $envelope ??= new Envelope(new Address('from@example.com'), [new Address('header@example.com')]);

            return new SentMessage($message, $envelope);
        }

        public function __toString(): string
        {
            return 'capture://';
        }
    };

    Mail::extend('capture', fn (): TransportInterface => $transport);

    config([
        'mail.mailers.capture' => ['transport' => 'capture'],
    ]);

    SendMailJob::dispatchSync((new TestMailable)->to('header@example.com'), 'capture');

    $log = MailSendLog::query()->first();

    expect($captured->headerValue)->toBe($log->correlation_id)
        ->and($log->correlation_id)->not->toBeEmpty();
});

test('successful send stores provider reference when read-back is enabled', function (): void {
    config([
        'mail-outbox.read_back_provider_id' => true,
        'mail-outbox.mailers.array' => ['read_back_provider_id' => true],
    ]);

    $this->app->instance(ProviderMessageIdReader::class, new class implements ProviderMessageIdReader
    {
        public function read(string $mailer, SentMessage $sentMessage): ?string
        {
            return 'provider-msg-123';
        }
    });

    SendMailJob::dispatchSync((new TestMailable)->to('p@example.com'), 'array');

    $log = MailSendLog::query()->first();

    expect($log->status)->toBe(MailSendStatus::Sent)
        ->and($log->provider_reference)->toBe('provider-msg-123')
        ->and($log->correlation_id)->not->toBeEmpty()
        ->and($log->message_id)->not->toBeEmpty()
        ->and($log->provider_reference)->not->toBe($log->message_id);
});

test('read-back can be disabled per mailer and send still succeeds', function (): void {
    config([
        'mail-outbox.read_back_provider_id' => true,
        'mail-outbox.mailers.bulk' => ['read_back_provider_id' => false],
        'mail.mailers.bulk' => ['transport' => 'array'],
    ]);

    $probe = new class
    {
        public bool $called = false;
    };

    $this->app->instance(ProviderMessageIdReader::class, new class($probe) implements ProviderMessageIdReader
    {
        public function __construct(private object $probe)
        {
        }

        public function read(string $mailer, SentMessage $sentMessage): ?string
        {
            $this->probe->called = true;

            return 'should-not-appear';
        }
    });

    SendMailJob::dispatchSync((new TestMailable)->to('bulk@example.com'), 'bulk');

    $log = MailSendLog::query()->first();

    expect($probe->called)->toBeFalse()
        ->and($log->status)->toBe(MailSendStatus::Sent)
        ->and($log->provider_reference)->toBeNull()
        ->and($log->correlation_id)->not->toBeEmpty();
});

test('failed provider read-back still records send as sent without provider reference', function (): void {
    config([
        'mail-outbox.mailers.array' => ['read_back_provider_id' => true],
    ]);

    $this->app->instance(ProviderMessageIdReader::class, new class implements ProviderMessageIdReader
    {
        public function read(string $mailer, SentMessage $sentMessage): ?string
        {
            throw new RuntimeException('read-back unavailable');
        }
    });

    SendMailJob::dispatchSync((new TestMailable)->to('ok@example.com'), 'array');

    $log = MailSendLog::query()->first();

    expect($log->status)->toBe(MailSendStatus::Sent)
        ->and($log->provider_reference)->toBeNull()
        ->and($log->error)->toBeNull();
});

test('default provider reader never treats Message-ID as provider reference', function (): void {
    $email = (new Email)
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('x')
        ->text('body');
    $email->getHeaders()->addIdHeader('Message-ID', 'rfc5322@example.com');

    $sent = new SentMessage(
        $email,
        new Envelope(new Address('from@example.com'), [new Address('to@example.com')]),
    );

    $reader = new SymfonySentMessageProviderIdReader;

    expect($reader->read('array', $sent))->toBeNull();
});

test('default provider reader returns provider-stamped header when present', function (): void {
    $email = (new Email)
        ->from('from@example.com')
        ->to('to@example.com')
        ->subject('x')
        ->text('body');
    $email->getHeaders()->addTextHeader('X-SES-Message-ID', 'ses-abc-123');
    $email->getHeaders()->addIdHeader('Message-ID', 'rfc5322@example.com');

    $sent = new SentMessage(
        $email,
        new Envelope(new Address('from@example.com'), [new Address('to@example.com')]),
    );

    $reader = new SymfonySentMessageProviderIdReader;

    expect($reader->read('ses', $sent))->toBe('ses-abc-123');
});
