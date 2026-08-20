<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Moox\MailInbox\Enums\ClaimResult;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\Exceptions\InvalidSyncCursorException;
use Moox\MailInbox\InboxDriverManager;
use Moox\MailInbox\InboxMessageDto;
use Moox\MailInbox\Jobs\FetchMailsJob;
use Moox\MailInbox\Jobs\HandleFailedJob;
use Moox\MailInbox\Jobs\ParsePdfJob;
use Moox\MailInbox\Jobs\StoreAttachmentsJob;
use Moox\MailInbox\Jobs\VerifyAttachmentProgressJob;
use Moox\MailInbox\MessagePage;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Models\MailInboxSyncState;
use Moox\MailInbox\Services\MailInboxService;
use Moox\MailInbox\Tests\Support\InMemoryDriver;

beforeEach(function () {
    config()->set('mail-inbox.connections', [
        'default' => [],
    ]);
    config()->set('mail-inbox.mailboxes', [
        'default' => [
            'driver' => 'memory',
            'connection' => 'default',
            'address' => 'inbox@example.com',
        ],
    ]);
    config()->set('mail-inbox.attachments', [
        'disk' => 'local',
        'path' => 'mail-inbox/attachments',
    ]);
    config()->set('mail-inbox.delta_max_pages_per_poll', 50);
    config()->set('mail-inbox.memory_limit', '256M');
    config()->set('mail-inbox.listener_timeout_minutes', 0);

    $this->fakeDriver = new InMemoryDriver;
    app()->forgetInstance(InboxDriverManager::class);
    $manager = app(InboxDriverManager::class);
    $manager->flush();
    $manager->register('memory', fn (): InMemoryDriver => $this->fakeDriver);
});

function pipelineDto(
    string $externalId = 'ext-1',
    string $messageId = '<msg-1@example.com>',
    bool $hasAttachments = true,
): InboxMessageDto {
    return new InboxMessageDto(
        externalId: $externalId,
        subject: 'Invoice',
        from: 'vendor@example.com',
        receivedAt: new DateTimeImmutable('2026-01-15T10:00:00Z'),
        bodyHtml: '<p>body</p>',
        bodyText: 'body',
        attachments: [],
        messageId: $messageId,
        fromName: 'Vendor',
        toEmail: 'inbox@example.com',
        toName: 'Inbox',
        hasAttachments: $hasAttachments,
    );
}

it('persists messages without attachment rows and dual-key dedups', function () {
    Bus::fake([StoreAttachmentsJob::class, ParsePdfJob::class]);

    $service = app(MailInboxService::class);
    $first = $service->persistMessages([pipelineDto()], 'default');
    expect($first->persisted)->toBe(1);

    $message = InboxMessage::query()->first();
    expect($message)->not->toBeNull();
    expect($message->external_id)->toBe('ext-1');
    expect($message->message_id)->toBe('<msg-1@example.com>');
    expect($message->attachments)->toHaveCount(0);

    $second = $service->persistMessages([
        pipelineDto(externalId: 'ext-1-immutable', messageId: '<msg-1@example.com>'),
    ], 'default');

    expect($second->persisted)->toBe(0);
    expect($second->skippedKnown)->toBe(1);
    expect(InboxMessage::query()->count())->toBe(1);
    expect(InboxMessage::query()->first()->external_id)->toBe('ext-1-immutable');
});

it('skips messages without attachments', function () {
    Bus::fake([StoreAttachmentsJob::class]);

    $result = app(MailInboxService::class)->persistMessages([
        pipelineDto(hasAttachments: false),
    ], 'default');

    expect($result->skippedNoAttachments)->toBe(1);
    expect(InboxMessage::query()->count())->toBe(0);
});

it('persists messages with hasAttachments but no file attachments', function () {
    Bus::fake([ParsePdfJob::class]);

    $result = app(MailInboxService::class)->persistMessages([
        pipelineDto(hasAttachments: true),
    ], 'default');

    expect($result->persisted)->toBe(1);

    $message = InboxMessage::query()->first();
    expect($message->attachments)->toHaveCount(0);

    (new StoreAttachmentsJob($message->id))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    expect($message->fresh()->processing_status)->toBe(InboxMessageProcessingStatus::Processed->value);
    expect($this->fakeDriver->outcomeFor('ext-1'))->toBe(SettlementOutcome::Processed);
});

it('fetches attachments for in-flight messages without attachment rows', function () {
    Bus::fake([ParsePdfJob::class]);

    $message = InboxMessage::create([
        'scope' => 'default',
        'channel' => 'email',
        'external_id' => 'ext-inflight',
        'message_id' => '<inflight@example.com>',
        'from_email' => 'vendor@example.com',
        'subject' => 'Invoice',
        'received_at' => now(),
        'has_attachments' => true,
        'processing_status' => InboxMessageProcessingStatus::New->value,
    ]);

    $this->fakeDriver->addFileAttachment('ext-inflight', 'att-pdf', '%PDF-bytes', 'invoice.pdf', 'application/pdf');

    (new StoreAttachmentsJob($message->id))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    $attachment = InboxAttachment::query()->first();
    expect($attachment)->not->toBeNull();
    expect($attachment->storage_path)->not->toBe('');
    expect($attachment->filesize)->toBe(strlen('%PDF-bytes'));
    expect($message->fresh()->processing_status)->toBe(InboxMessageProcessingStatus::New->value);
    expect($this->fakeDriver->outcomeFor('ext-inflight'))->toBeNull();

    Bus::assertDispatched(ParsePdfJob::class);
});

it('clears sync cursor when configured driver differs from stored driver', function () {
    Bus::fake([StoreAttachmentsJob::class, ParsePdfJob::class]);

    MailInboxSyncState::query()->create([
        'scope' => 'default',
        'driver' => 'other-driver',
        'delta_link' => 'stale-cursor-from-other',
        'last_synced_at' => null,
    ]);

    $this->fakeDriver = new InMemoryDriver([
        new MessagePage(
            messages: [pipelineDto(externalId: 'ext-new')],
            continuationCursor: null,
            resumeCursor: 'fresh-resume',
        ),
    ]);
    app(InboxDriverManager::class)->flush();
    app(InboxDriverManager::class)->register('memory', fn (): InMemoryDriver => $this->fakeDriver);

    (new FetchMailsJob('default'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    $sync = MailInboxSyncState::query()->find('default');
    expect($sync->driver)->toBe('memory');
    expect($sync->delta_link)->toBe('fresh-resume');
});

it('fetch job persists, claims, and stores resume cursor via fake driver', function () {
    Bus::fake([StoreAttachmentsJob::class, ParsePdfJob::class]);

    $this->fakeDriver = new InMemoryDriver([
        new MessagePage(
            messages: [pipelineDto()],
            continuationCursor: null,
            resumeCursor: 'resume-token-1',
        ),
    ]);
    app(InboxDriverManager::class)->flush();
    app(InboxDriverManager::class)->register('memory', fn (): InMemoryDriver => $this->fakeDriver);

    (new FetchMailsJob('default'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    expect(InboxMessage::query()->count())->toBe(1);
    expect($this->fakeDriver->claimedIds())->toHaveKey('ext-1');

    $sync = MailInboxSyncState::query()->find('default');
    expect($sync->delta_link)->toBe('resume-token-1');
    expect($sync->driver)->toBe('memory');
});

it('clears invalid sync cursor and recovers', function () {
    Bus::fake([StoreAttachmentsJob::class, ParsePdfJob::class]);

    MailInboxSyncState::query()->create([
        'scope' => 'default',
        'driver' => 'memory',
        'delta_link' => 'stale-cursor',
        'last_synced_at' => null,
    ]);

    $calls = 0;
    $inner = new InMemoryDriver([
        new MessagePage(
            messages: [pipelineDto()],
            continuationCursor: null,
            resumeCursor: 'fresh-resume',
        ),
    ]);

    $this->fakeDriver = new class($inner, $calls) extends InMemoryDriver
    {
        public function __construct(
            private InMemoryDriver $inner,
            private int &$calls,
        ) {
            parent::__construct();
        }

        public function fetch(?string $cursor = null): MessagePage
        {
            $this->calls++;
            if ($this->calls === 1 && $cursor === 'stale-cursor') {
                throw new InvalidSyncCursorException('stale');
            }

            return $this->inner->fetch($cursor === 'stale-cursor' ? null : $cursor);
        }

        public function claim(string $externalId): ClaimResult
        {
            return $this->inner->claim($externalId);
        }

        public function settledOutcomes(): array
        {
            return $this->inner->settledOutcomes();
        }

        public function claimedIds(): array
        {
            return $this->inner->claimedIds();
        }
    };

    app(InboxDriverManager::class)->flush();
    app(InboxDriverManager::class)->register('memory', fn (): InMemoryDriver => $this->fakeDriver);

    (new FetchMailsJob('default'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    expect(InboxMessage::query()->count())->toBe(1);
    expect(MailInboxSyncState::query()->find('default')->delta_link)->toBe('fresh-resume');
});

it('store attachments job lists from driver and settles processed when no pdfs', function () {
    Bus::fake([ParsePdfJob::class]);

    $message = InboxMessage::create([
        'scope' => 'default',
        'channel' => 'email',
        'external_id' => 'ext-xml',
        'message_id' => '<xml@example.com>',
        'from_email' => 'a@b.c',
        'subject' => 'xml',
        'received_at' => now(),
        'has_attachments' => true,
        'processing_status' => InboxMessageProcessingStatus::New->value,
    ]);

    $this->fakeDriver->addFileAttachment('ext-xml', 'att-xml', '<xml/>', 'doc.xml', 'application/xml');

    (new StoreAttachmentsJob($message->id))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    $attachment = InboxAttachment::query()->first();
    expect($attachment->storage_path)->not->toBe('');
    expect($attachment->checksum)->not->toBeNull();
    expect($attachment->filesize)->toBe(strlen('<xml/>'));
    expect($message->fresh()->processing_status)->toBe(InboxMessageProcessingStatus::Processed->value);
    expect($this->fakeDriver->outcomeFor('ext-xml'))->toBe(SettlementOutcome::Processed);
});

it('runs end-to-end pipeline against in-memory driver including failed and ignored settlement', function () {
    Bus::fake([ParsePdfJob::class, VerifyAttachmentProgressJob::class]);

    $duplicate = pipelineDto(externalId: 'ext-dup', messageId: '<same@example.com>');
    $fresh = pipelineDto(externalId: 'ext-2', messageId: '<other@example.com>');

    InboxMessage::create([
        'scope' => 'default',
        'channel' => 'email',
        'external_id' => 'ext-old',
        'message_id' => '<same@example.com>',
        'from_email' => 'vendor@example.com',
        'subject' => 'known',
        'received_at' => now()->subDay(),
        'has_attachments' => true,
        'processing_status' => InboxMessageProcessingStatus::Processed->value,
    ]);

    $this->fakeDriver = new InMemoryDriver([
        new MessagePage(
            messages: [$duplicate, $fresh],
            continuationCursor: null,
            resumeCursor: 'e2e-resume',
        ),
    ]);
    $this->fakeDriver->addFileAttachment('ext-2', 'att-pdf', '%PDF', 'invoice.pdf', 'application/pdf');
    $this->fakeDriver->addFileAttachment('ext-2', 'att-txt', 'hello', 'note.txt', 'text/plain');
    app(InboxDriverManager::class)->flush();
    app(InboxDriverManager::class)->register('memory', fn (): InMemoryDriver => $this->fakeDriver);

    (new FetchMailsJob('default'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    expect(InboxMessage::query()->count())->toBe(2);
    expect(InboxMessage::query()->where('message_id', '<same@example.com>')->value('external_id'))->toBe('ext-dup');

    $newMessage = InboxMessage::query()->where('external_id', 'ext-2')->first();
    expect($newMessage)->not->toBeNull();

    (new StoreAttachmentsJob($newMessage->id))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    Bus::assertDispatched(ParsePdfJob::class);

    $pdfAttachment = InboxAttachment::query()
        ->where('inbox_message_id', $newMessage->id)
        ->where('is_pdf', true)
        ->first();
    expect($pdfAttachment)->not->toBeNull();
    expect($pdfAttachment->storage_path)->not->toBe('');

    $pdfAttachment->markAsProcessing();

    (new VerifyAttachmentProgressJob($pdfAttachment->id))->handle(app(InboxDriverManager::class));

    expect($this->fakeDriver->outcomeFor('ext-2'))->toBe(SettlementOutcome::Ignored);

    $failedMessage = InboxMessage::create([
        'scope' => 'default',
        'channel' => 'email',
        'external_id' => 'ext-failed',
        'message_id' => '<failed@example.com>',
        'from_email' => 'vendor@example.com',
        'subject' => 'failed',
        'received_at' => now(),
        'has_attachments' => false,
        'processing_status' => InboxMessageProcessingStatus::New->value,
    ]);

    (new HandleFailedJob($failedMessage->id, 'pipeline error'))->handle(app(InboxDriverManager::class));

    expect($failedMessage->fresh()->processing_status)->toBe(InboxMessageProcessingStatus::Failed->value);
    expect($this->fakeDriver->outcomeFor('ext-failed'))->toBe(SettlementOutcome::Failed);
});

it('reports unconfigured sync-state scopes on status command', function () {
    MailInboxSyncState::query()->create([
        'scope' => 'legacy-intake',
        'driver' => 'memory',
        'delta_link' => 'cursor',
        'last_synced_at' => null,
    ]);

    Artisan::call('mail-inbox:status');

    expect(Artisan::output())
        ->toContain('legacy-intake')
        ->toContain('mail-inbox.mailboxes.legacy-intake');
});

it('reports nothing extra on status when every sync-state scope is configured', function () {
    MailInboxSyncState::query()->create([
        'scope' => 'default',
        'driver' => 'memory',
        'delta_link' => null,
        'last_synced_at' => null,
    ]);

    Artisan::call('mail-inbox:status');

    expect(Artisan::output())->not->toContain('without mailboxes configuration');
});

it('fails fetch with actionable error for unconfigured scope', function () {
    MailInboxSyncState::query()->create([
        'scope' => 'orphan',
        'driver' => 'memory',
        'delta_link' => null,
        'last_synced_at' => null,
    ]);

    expect(fn () => (new FetchMailsJob('orphan'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    ))->toThrow(InvalidArgumentException::class, 'mail-inbox.mailboxes.orphan');
});

it('fails when a driver raises InvalidSyncCursorException on every fetch', function () {
    MailInboxSyncState::query()->create([
        'scope' => 'default',
        'driver' => 'memory',
        'delta_link' => 'bad-cursor',
        'last_synced_at' => null,
    ]);

    $this->fakeDriver = new class extends InMemoryDriver
    {
        public function fetch(?string $cursor = null): MessagePage
        {
            throw new InvalidSyncCursorException('always invalid');
        }
    };
    app(InboxDriverManager::class)->flush();
    app(InboxDriverManager::class)->register('memory', fn (): InMemoryDriver => $this->fakeDriver);

    expect(fn () => (new FetchMailsJob('default'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    ))->toThrow(InvalidSyncCursorException::class);
});

it('warns on repeated cursor reset across runs', function () {
    Bus::fake([StoreAttachmentsJob::class, ParsePdfJob::class]);
    config()->set('mail-inbox.cursor_reset_warning_minutes', 60);

    MailInboxSyncState::query()->create([
        'scope' => 'default',
        'driver' => 'memory',
        'delta_link' => 'stale-cursor',
        'cursor_reset_at' => now()->subMinutes(5),
        'last_synced_at' => null,
    ]);

    $calls = 0;
    $inner = new InMemoryDriver([
        new MessagePage(
            messages: [pipelineDto(externalId: 'ext-reset')],
            continuationCursor: null,
            resumeCursor: 'fresh-resume',
        ),
    ]);

    $this->fakeDriver = new class($inner, $calls) extends InMemoryDriver
    {
        public function __construct(
            private InMemoryDriver $inner,
            private int &$calls,
        ) {
            parent::__construct();
        }

        public function fetch(?string $cursor = null): MessagePage
        {
            $this->calls++;
            if ($this->calls === 1 && $cursor === 'stale-cursor') {
                throw new InvalidSyncCursorException('stale');
            }

            return $this->inner->fetch($cursor === 'stale-cursor' ? null : $cursor);
        }

        public function claim(string $externalId): ClaimResult
        {
            return $this->inner->claim($externalId);
        }
    };

    app(InboxDriverManager::class)->flush();
    app(InboxDriverManager::class)->register('memory', fn (): InMemoryDriver => $this->fakeDriver);

    $repeatedResetLogged = false;

    Log::shouldReceive('channel')->with('mail-inbox')->andReturnSelf();
    Log::shouldReceive('warning')->andReturnUsing(function (string $message, array $context = []) use (&$repeatedResetLogged): void {
        if ($message === '[MailInbox] Repeated sync cursor reset for scope') {
            $repeatedResetLogged = true;
            expect($context['scope'] ?? null)->toBe('default');
        }
    });
    Log::shouldReceive('info')->zeroOrMoreTimes();
    Log::shouldReceive('debug')->zeroOrMoreTimes();
    Log::shouldReceive('error')->zeroOrMoreTimes();

    (new FetchMailsJob('default'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    expect($repeatedResetLogged)->toBeTrue();
});

it('marks catch-up idle when fetch completes with resume cursor', function () {
    Bus::fake([StoreAttachmentsJob::class, ParsePdfJob::class]);

    $this->fakeDriver = new InMemoryDriver([
        new MessagePage(
            messages: [pipelineDto()],
            continuationCursor: null,
            resumeCursor: 'resume-token-1',
        ),
    ]);
    app(InboxDriverManager::class)->flush();
    app(InboxDriverManager::class)->register('memory', fn (): InMemoryDriver => $this->fakeDriver);

    (new FetchMailsJob('default'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    $sync = MailInboxSyncState::query()->find('default');
    expect($sync->catch_up_in_progress)->toBeFalse();
});

it('marks catch-up in progress when fetch defers continuation at page cap', function () {
    Bus::fake([StoreAttachmentsJob::class, ParsePdfJob::class]);

    config()->set('mail-inbox.delta_max_pages_per_poll', 1);

    $this->fakeDriver = new InMemoryDriver([
        new MessagePage(
            messages: [pipelineDto(externalId: 'ext-page-1')],
            continuationCursor: 'page-2',
            resumeCursor: null,
        ),
        new MessagePage(
            messages: [pipelineDto(externalId: 'ext-page-2', messageId: '<msg-2@example.com>')],
            continuationCursor: null,
            resumeCursor: 'resume-token-2',
        ),
    ]);
    app(InboxDriverManager::class)->flush();
    app(InboxDriverManager::class)->register('memory', fn (): InMemoryDriver => $this->fakeDriver);

    (new FetchMailsJob('default'))->handle(
        app(InboxDriverManager::class),
        app(MailInboxService::class),
    );

    $sync = MailInboxSyncState::query()->find('default');
    expect($sync->delta_link)->toBe('page-2');
    expect($sync->catch_up_in_progress)->toBeTrue();
});
