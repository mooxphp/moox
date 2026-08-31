<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Illuminate\Support\Facades\Mail;
use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Exceptions\MessageTooLargeException;
use Moox\MailOutbox\Exceptions\TransientMailFailureException;
use Moox\MailOutbox\Jobs\SendMailJob;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Tests\Support\TestMailable;
use Moox\MailOutbox\Tests\Support\ThrowingTransport;
use Moox\MailOutbox\Tests\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

uses(TestCase::class);

function bindThrowingMailer(string $name, ThrowingTransport $transport): void
{
    Mail::extend($name, fn (): TransportInterface => $transport);

    config([
        "mail.mailers.{$name}" => [
            'transport' => $name,
        ],
    ]);
}

/**
 * @param  callable(QueueJobContract): void|null  $configure
 */
function runSendMailJobWithAttempts(SendMailJob $job, int $attempt, ?callable $configure = null): void
{
    $queueJob = Mockery::mock(QueueJobContract::class);
    $queueJob->shouldReceive('attempts')->andReturn($attempt);
    $queueJob->shouldReceive('getJobId')->andReturn('test-job-'.$attempt);
    $queueJob->shouldReceive('uuid')->andReturn('uuid-'.$attempt);
    $queueJob->shouldReceive('fail')->andReturnNull()->byDefault();
    $queueJob->shouldReceive('release')->andReturnNull()->byDefault();
    $queueJob->shouldReceive('delete')->andReturnNull()->byDefault();
    $queueJob->shouldReceive('isDeleted')->andReturn(false)->byDefault();
    $queueJob->shouldReceive('isReleased')->andReturn(false)->byDefault();
    $queueJob->shouldReceive('hasFailed')->andReturn(false)->byDefault();
    $queueJob->shouldReceive('isDeletedOrReleased')->andReturn(false)->byDefault();
    $queueJob->shouldReceive('payload')->andReturn([])->byDefault();

    if ($configure !== null) {
        $configure($queueJob);
    }

    $job->setJob($queueJob);
    app()->call([$job, 'handle']);
}

test('oversized message fails with domain error before transport and is logged as failed', function (): void {
    config(['mail-outbox.max_message_bytes' => 10]);

    $transport = new class implements TransportInterface
    {
        public int $sendAttempts = 0;

        public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
        {
            $this->sendAttempts++;

            return null;
        }

        public function __toString(): string
        {
            return 'probe://';
        }
    };

    Mail::extend('probe', fn (): TransportInterface => $transport);

    config([
        'mail.mailers.probe' => [
            'transport' => 'probe',
        ],
    ]);

    $job = new SendMailJob(
        (new TestMailable(body: str_repeat('x', 100)))->to('recipient@example.com'),
        'probe',
    );

    try {
        runSendMailJobWithAttempts($job, 1);
    } catch (MessageTooLargeException|Throwable) {
        // sync fail() may rethrow
    }

    expect($transport->sendAttempts)->toBe(0)
        ->and(MailSendLog::query()->count())->toBe(1);

    $log = MailSendLog::query()->first();

    expect($log->status)->toBe(MailSendStatus::Failed)
        ->and($log->attempt_count)->toBe(1)
        ->and($log->error)->toContain('exceeds configured ceiling');
});

test('permanent transport failure is terminal on first attempt', function (): void {
    config(['mail-outbox.retry.max_tries' => 5]);

    $exception = new TransportException('550 recipient rejected: mailbox unavailable');
    $transport = new ThrowingTransport($exception);
    bindThrowingMailer('permanent', $transport);

    $job = new SendMailJob((new TestMailable)->to('bad@example.com'), 'permanent');

    try {
        runSendMailJobWithAttempts($job, 1);
    } catch (Throwable) {
        // expected
    }

    expect($transport->sendAttempts)->toBe(1)
        ->and(MailSendLog::query()->count())->toBe(1);

    $log = MailSendLog::query()->first();

    expect($log->status)->toBe(MailSendStatus::Failed)
        ->and($log->attempt_count)->toBe(1)
        ->and($log->error)->toContain('recipient rejected');
});

test('transient transport failure keeps row queued until retries are exhausted', function (): void {
    config([
        'mail-outbox.retry.max_tries' => 3,
        'mail-outbox.retry.backoff' => [0, 0, 0],
    ]);

    $exception = new TransientMailFailureException('Connection timed out', retryAfterSeconds: null);
    $transport = new ThrowingTransport($exception);
    bindThrowingMailer('transient', $transport);

    $job = new SendMailJob((new TestMailable)->to('retry@example.com'), 'transient');

    try {
        runSendMailJobWithAttempts($job, 1);
        $this->fail('Expected transient failure to rethrow for retry');
    } catch (TransientMailFailureException) {
        // expected — queue will retry
    }

    expect(MailSendLog::query()->count())->toBe(1);

    $log = MailSendLog::query()->first();

    expect($log->status)->toBe(MailSendStatus::Queued)
        ->and($log->attempt_count)->toBe(1)
        ->and($log->error)->toContain('timed out');

    try {
        runSendMailJobWithAttempts($job, 2);
    } catch (TransientMailFailureException) {
        // still retrying
    }

    expect($log->fresh()->status)->toBe(MailSendStatus::Queued)
        ->and($log->fresh()->attempt_count)->toBe(2);

    try {
        runSendMailJobWithAttempts($job, 3);
    } catch (Throwable) {
        // exhausted
    }

    expect($transport->sendAttempts)->toBe(3)
        ->and(MailSendLog::query()->count())->toBe(1)
        ->and($log->fresh()->status)->toBe(MailSendStatus::Failed)
        ->and($log->fresh()->attempt_count)->toBe(3);
});

test('provider-requested retry delay is honoured for transient failures', function (): void {
    config(['mail-outbox.retry.max_tries' => 5]);

    $exception = new TransientMailFailureException('Rate limited', retryAfterSeconds: 120);
    $transport = new ThrowingTransport($exception);
    bindThrowingMailer('delayed', $transport);

    $job = new SendMailJob((new TestMailable)->to('delay@example.com'), 'delayed');

    runSendMailJobWithAttempts($job, 1, function (QueueJobContract $queueJob): void {
        $queueJob->shouldReceive('release')->once()->with(120);
        $queueJob->shouldReceive('fail')->never();
    });

    expect(MailSendLog::query()->first()->status)->toBe(MailSendStatus::Queued);
});

test('zero provider retry-after is clamped to at least one second', function (): void {
    config(['mail-outbox.retry.max_tries' => 5]);

    $exception = new TransientMailFailureException('Rate limited', retryAfterSeconds: 0);
    $transport = new ThrowingTransport($exception);
    bindThrowingMailer('zero-delay', $transport);

    $job = new SendMailJob((new TestMailable)->to('zero@example.com'), 'zero-delay');

    runSendMailJobWithAttempts($job, 1, function (QueueJobContract $queueJob): void {
        $queueJob->shouldReceive('release')->once()->with(1);
        $queueJob->shouldReceive('fail')->never();
    });

    expect(MailSendLog::query()->first()->status)->toBe(MailSendStatus::Queued);
});

test('failed() marks a non-sent log row as failed', function (): void {
    $job = new SendMailJob((new TestMailable)->to('fail@example.com'), 'array');

    $log = MailSendLog::query()->create([
        'mailer' => 'array',
        'intended_recipients' => ['fail@example.com'],
        'actual_recipients' => null,
        'subject' => 'Mail outbox test',
        'status' => MailSendStatus::Queued,
        'attempt_count' => 1,
        'error' => null,
        'message_id' => null,
        'provider_reference' => null,
        'correlation_id' => 'corr-failed-hook',
        'related_type' => null,
        'related_id' => null,
    ]);

    $job->mailSendLogId = $log->id;
    $job->failed(new RuntimeException('queue gave up'));

    expect($log->fresh()->status)->toBe(MailSendStatus::Failed)
        ->and($log->fresh()->error)->toBe('queue gave up');
});
