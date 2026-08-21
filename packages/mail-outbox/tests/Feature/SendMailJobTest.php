<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Jobs\SendMailJob;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Tests\Support\RelatedModel;
use Moox\MailOutbox\Tests\Support\TestMailable;
use Moox\MailOutbox\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    if (! Schema::hasTable('mail_outbox_related_models')) {
        Schema::create('mail_outbox_related_models', function ($table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }
});

test('dispatching SendMailJob sends mail and leaves exactly one queued-to-sent log row', function (): void {
    Mail::fake();

    $mailable = (new TestMailable)->to('recipient@example.com');

    SendMailJob::dispatchSync($mailable, 'array');

    Mail::assertSent(TestMailable::class, function (TestMailable $mail): bool {
        return $mail->hasTo('recipient@example.com');
    });

    expect(MailSendLog::query()->count())->toBe(1);

    $log = MailSendLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->mailer)->toBe('array')
        ->and($log->status)->toBe(MailSendStatus::Sent)
        ->and($log->attempt_count)->toBe(1)
        ->and($log->error)->toBeNull()
        ->and($log->subject)->toBe('Mail outbox test')
        ->and($log->intended_recipients)->toContain('recipient@example.com')
        ->and($log->correlation_id)->not->toBeEmpty()
        ->and(in_array(JobProgress::class, class_uses_recursive(SendMailJob::class), true))->toBeTrue();
});

test('array transport captures rfc 5322 message id without inventing one', function (): void {
    SendMailJob::dispatchSync((new TestMailable)->to('id@example.com'), 'array');

    $log = MailSendLog::query()->first();

    expect($log->status)->toBe(MailSendStatus::Sent)
        ->and($log->message_id)->not->toBeNull()
        ->and($log->message_id)->not->toContain('@mail-outbox');
});

test('related business object can be attached and resolved via morph', function (): void {
    Mail::fake();

    $related = RelatedModel::query()->create(['name' => 'order-1']);
    $mailable = (new TestMailable)->to('recipient@example.com');

    SendMailJob::dispatchSync($mailable, 'array', $related);

    $log = MailSendLog::query()->first();

    expect($log->related)->not->toBeNull()
        ->and($log->related->is($related))->toBeTrue()
        ->and($log->related_type)->toBe($related->getMorphClass())
        ->and($log->related_id)->toBe($related->getKey());
});

test('two differently named mailers are recorded correctly', function (): void {
    config([
        'mail.mailers.docs' => ['transport' => 'array'],
        'mail.mailers.bulk' => ['transport' => 'array'],
    ]);

    Mail::fake();

    SendMailJob::dispatchSync((new TestMailable)->to('a@example.com'), 'docs');
    SendMailJob::dispatchSync((new TestMailable)->to('b@example.com'), 'bulk');

    expect(MailSendLog::query()->pluck('mailer')->sort()->values()->all())->toBe(['bulk', 'docs']);
});
