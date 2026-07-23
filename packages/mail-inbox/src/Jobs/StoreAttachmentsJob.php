<?php

declare(strict_types=1);

namespace Moox\MailInbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Services\GraphMailService;
use Moox\MailInbox\Services\MailInboxService;
use Throwable;

class StoreAttachmentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300];

    public function __construct(
        public int $inboxMessageId,
    ) {
    }

    public function handle(GraphMailService $graph, MailInboxService $inbox): void
    {
        $this->applyMemoryLimit();
        $this->setProgress(0);

        $message = InboxMessage::query()->find($this->inboxMessageId);

        if ($message === null) {
            Log::channel('mail-inbox')->error('[MailInbox] StoreAttachmentsJob: message not found', [
                'inbox_message_id' => $this->inboxMessageId,
            ]);
            $this->setProgress(100);

            return;
        }

        $externalId = $message->external_id;
        if ($externalId === null || $externalId === '') {
            Log::channel('mail-inbox')->error('[MailInbox] Cannot fetch attachments: message has no external_id', [
                'inbox_message_id' => $message->id,
            ]);
            throw new InvalidArgumentException('Inbox message has no external_id');
        }

        $attachments = $graph->fetchAttachments($externalId);
        $total = max(1, $attachments->count());
        $disk = (string) config('mail-inbox.attachments.disk');
        $basePath = trim((string) config('mail-inbox.attachments.path'), '/');

        $pdfAttachmentIds = [];
        $i = 0;

        foreach ($attachments as $attachment) {
            $i++;

            if (! $attachment instanceof FileAttachment) {
                $this->setProgress((int) round(($i / $total) * 90));

                continue;
            }

            $attachmentId = $attachment->getId();
            if ($attachmentId === null) {
                Log::channel('mail-inbox')->error('[MailInbox] Skipping attachment with null id', [
                    'message_external_id' => $externalId,
                ]);
                $this->setProgress((int) round(($i / $total) * 90));

                continue;
            }

            if (InboxAttachment::query()
                ->where('inbox_message_id', $message->id)
                ->where('external_attachment_id', $attachmentId)
                ->exists()
            ) {
                $existing = InboxAttachment::query()
                    ->where('inbox_message_id', $message->id)
                    ->where('external_attachment_id', $attachmentId)
                    ->first();
                if ($existing !== null && $existing->is_pdf && $existing->processing_status === InboxAttachmentProcessingStatus::New->value) {
                    $pdfAttachmentIds[] = $existing->id;
                }
                $this->setProgress((int) round(($i / $total) * 90));

                continue;
            }

            $payload = $graph->downloadAttachmentContent($externalId, $attachmentId);
            $contentBytes = $payload['contentBytes'];
            $filename = $attachment->getName() ?? 'attachment';

            $relativePath = "{$basePath}/{$message->scope}/".now()->format('Y/m/d')."/msg-{$message->id}/{$filename}";

            Storage::disk($disk)->put($relativePath, $contentBytes);

            $checksum = hash('sha256', $contentBytes);
            $mimeType = $attachment->getContentType() ?? 'application/octet-stream';
            $isPdf = $mimeType === 'application/pdf'
                || str_ends_with(strtolower($filename), '.pdf');

            $role = match (true) {
                $isPdf => 'invoice_pdf',
                $this->mimeLooksLikeXml($mimeType) => 'xml',
                default => 'other',
            };

            $inboxAttachment = InboxAttachment::create([
                'scope' => $message->scope,
                'inbox_message_id' => $message->id,
                'external_attachment_id' => $attachmentId,
                'storage_disk' => $disk,
                'storage_path' => $relativePath,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'extension' => pathinfo($filename, PATHINFO_EXTENSION) ?: null,
                'filesize' => $attachment->getSize(),
                'checksum' => $checksum,
                'is_pdf' => $isPdf,
                'attachment_role' => $role,
                'processing_status' => InboxAttachmentProcessingStatus::New->value,
            ]);

            if (! $isPdf) {
                $inboxAttachment->markAsSkipped();
            } else {
                $pdfAttachmentIds[] = $inboxAttachment->id;
            }

            $this->setProgress((int) round(($i / $total) * 90));
        }

        $message->refresh();

        if ($message->pdfAttachments()->exists()) {
            foreach ($pdfAttachmentIds as $pdfId) {
                ParsePdfJob::dispatch($pdfId);
            }
        } else {
            $inbox->finalizeMessageProcessingAfterAttachments($message->fresh(['attachments']));
        }

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        try {
            HandleFailedJob::dispatchSync(
                $this->inboxMessageId,
                $exception?->getMessage() ?? 'StoreAttachmentsJob failed'
            );
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->error('[MailInbox] HandleFailedJob also failed', [
                'inbox_message_id' => $this->inboxMessageId,
                'original_error' => $exception?->getMessage(),
                'handler_error' => $e->getMessage(),
            ]);
        }
    }

    private function applyMemoryLimit(): void
    {
        ini_set('memory_limit', (string) config('mail-inbox.memory_limit', '512M'));
    }

    private function mimeLooksLikeXml(string $mimeType): bool
    {
        $lower = strtolower($mimeType);

        return str_contains($lower, 'xml')
            || $lower === 'application/xml'
            || $lower === 'text/xml';
    }
}
