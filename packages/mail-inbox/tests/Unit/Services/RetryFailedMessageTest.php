<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;
use Moox\MailInbox\Jobs\ParsePdfJob;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Services\MailInboxService;

beforeEach(function () {
    config()->set('mail-inbox.retry_staleness_minutes', 30);
});

it('retryFailedMessage resets failed state and dispatches parse jobs', function () {
    Bus::fake([ParsePdfJob::class]);

    $message = InboxMessage::query()->create([
        'scope' => 'default',
        'external_id' => 'ext-retry-1',
        'message_id' => '<retry-1@example.com>',
        'subject' => 'Retry me',
        'from_email' => 'vendor@example.com',
        'processing_status' => InboxMessageProcessingStatus::Failed->value,
        'error_message' => 'Previous failure',
        'has_attachments' => true,
        'received_at' => now(),
    ]);

    InboxAttachment::query()->create([
        'inbox_message_id' => $message->id,
        'scope' => 'default',
        'external_attachment_id' => 'att-1',
        'filename' => 'invoice.pdf',
        'mime_type' => 'application/pdf',
        'is_pdf' => true,
        'processing_status' => InboxAttachmentProcessingStatus::Failed->value,
        'error_message' => 'Parse failed',
        'storage_disk' => 'local',
        'storage_path' => 'mail-inbox/attachments/test.pdf',
        'filesize' => 100,
    ]);

    app(MailInboxService::class)->retryFailedMessage($message);

    $message->refresh();
    expect($message->processing_status)->toBe(InboxMessageProcessingStatus::New->value);
    expect($message->error_message)->toBeNull();

    $attachment = $message->attachments()->first();
    expect($attachment->processing_status)->toBe(InboxAttachmentProcessingStatus::New->value);
    expect($attachment->error_message)->toBeNull();

    Bus::assertDispatched(ParsePdfJob::class);
});
