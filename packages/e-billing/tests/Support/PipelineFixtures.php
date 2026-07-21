<?php

declare(strict_types=1);

namespace Moox\EBilling\Tests\Support;

use Illuminate\Support\Facades\Storage;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;

final class PipelineFixtures
{
    /**
     * @param  array<string, mixed>  $billData
     * @return array{message: InboxMessage, attachment: InboxAttachment, document: EbillingDocument}
     */
    public static function hybridPipelineAttachment(array $billData, string $scope = 'test'): array
    {
        $attachmentDisk = 'local';
        $storagePath = 'inbox/minimal-invoice.pdf';
        $fixturePath = dirname(__DIR__).'/fixtures/minimal-invoice.pdf';

        Storage::fake($attachmentDisk);
        Storage::disk($attachmentDisk)->put($storagePath, (string) file_get_contents($fixturePath));

        Storage::fake('zugferd');

        $message = InboxMessage::query()->create([
            'scope' => $scope,
            'channel' => 'email',
            'external_id' => 'ext-'.uniqid(),
            'message_id' => 'msg-'.uniqid(),
            'processing_status' => 'processing',
            'has_attachments' => true,
        ]);

        $attachment = InboxAttachment::query()->create([
            'scope' => $scope,
            'inbox_message_id' => $message->id,
            'storage_disk' => $attachmentDisk,
            'storage_path' => $storagePath,
            'filename' => 'minimal-invoice.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'is_pdf' => true,
            'processing_status' => InboxAttachmentProcessingStatus::Processing->value,
        ]);

        $document = EbillingDocument::query()->create([
            'source_type' => $attachment->getMorphClass(),
            'source_id' => (string) $attachment->getKey(),
            'scope' => $scope,
            'format' => 'zugferd',
            'bill_data' => $billData,
            'gateway_status' => EBillingAttachmentProcessingStatus::Generating,
            'review_status' => InvoiceProcessingStatus::ParserCreated,
        ]);

        return [
            'message' => $message,
            'attachment' => $attachment,
            'document' => $document->fresh(),
        ];
    }
}
