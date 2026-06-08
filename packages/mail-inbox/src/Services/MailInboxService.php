<?php

declare(strict_types=1);

namespace Moox\MailInbox\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\Message;
use Moox\MailInbox\DeltaPersistResult;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;
use Moox\MailInbox\Exceptions\GraphItemNotFoundException;
use Moox\MailInbox\Jobs\ParsePdfJob;
use Moox\MailInbox\Jobs\StoreAttachmentsJob;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;

class MailInboxService
{
    public function __construct(
        private GraphMailService $graphService,
    ) {}

    /**
     * @param  array<int, Message>  $graphMessages
     */
    public function persistDeltaMessages(array $graphMessages, string $scope): DeltaPersistResult
    {
        $persisted = 0;
        $skippedKnown = 0;
        $skippedNoAttachments = 0;

        foreach ($graphMessages as $graphMessage) {
            if (! $graphMessage instanceof Message) {
                continue;
            }

            $externalId = $graphMessage->getId();
            if ($externalId === null || $externalId === '') {
                Log::channel('mail-inbox')->error('[MailInbox] Skipping Graph message with null id during delta persist');

                continue;
            }

            if (! ($graphMessage->getHasAttachments() ?? false)) {
                $skippedNoAttachments++;

                continue;
            }

            $internetId = $graphMessage->getInternetMessageId();
            $internetPresent = $internetId !== null && $internetId !== '';

            if ($internetPresent) {
                $existing = InboxMessage::query()
                    ->where('scope', $scope)
                    ->where('message_id', $internetId)
                    ->first();

                if ($existing !== null) {
                    if ($existing->external_id !== $externalId) {
                        $currentExternalId = $existing->external_id;

                        try {
                            // Migrate volatile → immutable as Delta re-delivers this mail (Prefer immutable on Graph traffic).
                            $existing->external_id = $externalId;
                            $existing->saveQuietly();

                            Log::channel('mail-inbox')->info('[MailInbox] Updated external_id on known message (likely volatile → immutable migration)', [
                                'scope' => $scope,
                                'inbox_message_id' => $existing->id,
                                'message_id' => $internetId,
                            ]);
                        } catch (UniqueConstraintViolationException) {
                            $existing->refresh();

                            /** @var int|string|null $conflictingId */
                            $conflictingId = InboxMessage::query()
                                ->where('scope', $scope)
                                ->where('external_id', $externalId)
                                ->whereKeyNot($existing->id)
                                ->value('id');

                            Log::channel('mail-inbox')->warning('[MailInbox] Could not update external_id on known message due to unique constraint', [
                                'scope' => $scope,
                                'inbox_message_id' => $existing->id,
                                'conflicting_inbox_message_id' => $conflictingId,
                                'message_id' => $internetId,
                                'attempted_external_id' => $externalId,
                                'current_external_id' => $currentExternalId,
                            ]);
                        }
                    }

                    Log::channel('mail-inbox')->debug('[MailInbox] Delta returned known message, skipping (pre-check)', [
                        'external_id' => $externalId,
                        'message_id' => $internetId,
                        'scope' => $scope,
                    ]);
                    $skippedKnown++;

                    continue;
                }
            } else {
                Log::channel('mail-inbox')->warning('[MailInbox] Delta message missing internetMessageId, falling back to external_id for dedup', [
                    'external_id' => $externalId,
                    'scope' => $scope,
                ]);

                // Dedupe uses Graph id equals row.external_id; there is nothing to reconcile (no RFC822 anchor for a stale-id update).
                $existsAlready = InboxMessage::query()
                    ->where('scope', $scope)
                    ->where('external_id', $externalId)
                    ->exists();

                if ($existsAlready) {
                    Log::channel('mail-inbox')->debug('[MailInbox] Delta returned known message, skipping (pre-check)', [
                        'external_id' => $externalId,
                        'message_id' => null,
                        'scope' => $scope,
                    ]);
                    $skippedKnown++;

                    continue;
                }
            }

            try {
                $row = $this->createInboxMessageFromGraphMessage($graphMessage, $scope);

                if ($row !== null) {
                    StoreAttachmentsJob::dispatch($row->id);
                    $persisted++;
                }
            } catch (UniqueConstraintViolationException) {
                $skippedKnown++;

                $internetMessageIdForLog = $internetPresent ? $internetId : null;

                $payload = [
                    'scope' => $scope,
                    'message_id' => $internetMessageIdForLog,
                    'external_id' => $externalId,
                ];

                try {
                    $colliding = $this->findCollidingInboxMessageForUniqueViolation($scope, $externalId, $internetMessageIdForLog);

                    if ($colliding === null) {
                        $payload['db_match'] = 'not_found';
                    } else {
                        $payload['db_id'] = $colliding->id;
                        $payload['db_external_id'] = $colliding->external_id;
                        $payload['db_message_id'] = $colliding->message_id;
                        $payload['db_subject'] = $colliding->subject;
                        $payload['db_created_at'] = $colliding->created_at?->toIso8601String();
                    }
                } catch (\Throwable $diagnosticError) {
                    $payload['db_match'] = 'diagnostic_query_failed';
                    $payload['diagnostic_error'] = $diagnosticError::class.': '.$diagnosticError->getMessage();
                }

                Log::channel('mail-inbox')->info('[MailInbox] Delta race condition caught by unique constraint, skipping', $payload);
            }
        }

        return new DeltaPersistResult(
            persisted: $persisted,
            skippedKnown: $skippedKnown,
            skippedNoAttachments: $skippedNoAttachments,
        );
    }

    public function finalizeMessageProcessingAfterAttachments(InboxMessage $message): void
    {
        $message = $message->fresh(['attachments']);

        if ($message === null) {
            return;
        }

        if ($message->processing_status !== InboxMessageProcessingStatus::PartiallyFailed->value
            && in_array($message->processing_status, [
                InboxMessageProcessingStatus::Processed->value,
                InboxMessageProcessingStatus::Failed->value,
            ], true)
        ) {
            return;
        }

        foreach ($message->attachments as $attachment) {
            if ($attachment->processing_status === InboxAttachmentProcessingStatus::New->value && ! $attachment->is_pdf) {
                $attachment->markAsSkipped();
            }
        }

        $message->load('attachments');

        if ($message->processing_status === InboxMessageProcessingStatus::PartiallyFailed->value
            && $message->attachments->isEmpty()
        ) {
            $error = $message->error_message !== null && $message->error_message !== ''
                ? $message->error_message
                : 'Attachment storage failed';
            $message->markAsFailed($error);
            $this->tryMoveGraphMessageToTerminalFolder($message->external_id, false, $message->id, $message->scope);

            return;
        }

        if ($message->attachments->contains(
            fn (InboxAttachment $a): bool => in_array($a->processing_status, [
                InboxAttachmentProcessingStatus::New->value,
                InboxAttachmentProcessingStatus::Processing->value,
            ], true)
        )) {
            return;
        }

        $hasFailed = $message->attachments->contains(
            fn (InboxAttachment $a): bool => $a->processing_status === InboxAttachmentProcessingStatus::Failed->value
        );

        $allPdfs = $message->pdfAttachments()->get();

        if ($message->processing_status === InboxMessageProcessingStatus::PartiallyFailed->value) {
            if ($hasFailed) {
                $error = $message->error_message !== null && $message->error_message !== ''
                    ? $message->error_message
                    : 'One or more attachments failed processing';
                $message->markAsFailed($error);
                $this->tryMoveGraphMessageToTerminalFolder($message->external_id, false, $message->id, $message->scope);
            } else {
                $message->error_message = null;
                $message->markAsProcessed();
                $this->tryMoveGraphMessageToTerminalFolder($message->external_id, true, $message->id, $message->scope);
            }

            return;
        }

        if ($allPdfs->isEmpty()) {
            $message->markAsProcessed();
            $this->tryMoveGraphMessageToTerminalFolder($message->external_id, true, $message->id, $message->scope);

            return;
        }

        if ($hasFailed) {
            $message->markAsFailed('One or more attachments failed processing');
            $this->tryMoveGraphMessageToTerminalFolder($message->external_id, false, $message->id, $message->scope);

            return;
        }

        if ($allPdfs->every(
            fn (InboxAttachment $a): bool => $a->processing_status === InboxAttachmentProcessingStatus::Processed->value
        )) {
            $message->markAsProcessed();
            $this->tryMoveGraphMessageToTerminalFolder($message->external_id, true, $message->id, $message->scope);
        }
    }

    private function tryMoveGraphMessageToTerminalFolder(?string $externalId, bool $success, ?int $inboxMessageId = null, ?string $scope = null): void
    {
        if ($externalId === null || $externalId === '') {
            return;
        }

        try {
            $this->graphService->moveGraphMessageToProcessedOrFailedFolder($externalId, $success, $scope);
        } catch (GraphItemNotFoundException $e) {
            Log::channel('mail-inbox')->warning('[MailInbox] Terminal folder move skipped: Graph message not found', [
                'external_id' => $externalId,
                'inbox_message_id' => $inboxMessageId,
                'success_path' => $success,
                'exception' => $e,
            ]);
        } catch (\Throwable $e) {
            Log::channel('mail-inbox')->error('[MailInbox] Terminal folder move failed', [
                'exception' => $e,
                'external_id' => $externalId,
                'inbox_message_id' => $inboxMessageId,
                'success_path' => $success,
            ]);
        }
    }

    public function enqueueParseJobsForInboxMessage(InboxMessage $message): void
    {
        $message->load('attachments');

        foreach ($message->attachments as $attachment) {
            if ($attachment->processing_status === InboxAttachmentProcessingStatus::New->value && ! $attachment->is_pdf) {
                $attachment->markAsSkipped();
            }
        }

        $pdfNew = $message->pdfAttachments()
            ->where('processing_status', InboxAttachmentProcessingStatus::New->value)
            ->get();

        if ($pdfNew->isEmpty()) {
            $this->finalizeMessageProcessingAfterAttachments($message->fresh(['attachments']));

            return;
        }

        foreach ($pdfNew as $attachment) {
            ParsePdfJob::dispatch($attachment->id);
        }
    }

    public function processNewMessages(string $scope = 'default'): int
    {
        $messages = InboxMessage::forScope($scope)->new()->with('attachments')->get();

        foreach ($messages as $message) {
            $this->enqueueParseJobsForInboxMessage($message);
        }

        return $messages->count();
    }

    public function retryFailedMessages(string $scope = 'default'): int
    {
        $messages = InboxMessage::forScope($scope)->failed()->with('attachments')->get();

        $stalenessMinutes = max(1, (int) config('mail-inbox.retry_staleness_minutes', 30));
        $stalenessThreshold = now()->subMinutes($stalenessMinutes);

        foreach ($messages as $message) {
            $messageId = $message->id;

            DB::transaction(function () use ($message, $stalenessThreshold): void {
                $message->update([
                    'processing_status' => InboxMessageProcessingStatus::New->value,
                    'error_message' => null,
                ]);

                $message->attachments()
                    ->where('processing_status', InboxAttachmentProcessingStatus::Failed->value)
                    ->update([
                        'processing_status' => InboxAttachmentProcessingStatus::New->value,
                        'error_message' => null,
                    ]);

                $message->attachments()
                    ->where('processing_status', InboxAttachmentProcessingStatus::Processing->value)
                    ->where('updated_at', '<', $stalenessThreshold)
                    ->update([
                        'processing_status' => InboxAttachmentProcessingStatus::New->value,
                        'error_message' => null,
                    ]);
            });

            $recentProcessingCount = InboxAttachment::query()
                ->where('inbox_message_id', $messageId)
                ->where('processing_status', InboxAttachmentProcessingStatus::Processing->value)
                ->where('updated_at', '>=', $stalenessThreshold)
                ->count();

            if ($recentProcessingCount > 0) {
                Log::channel('mail-inbox')->warning('[MailInbox] retryFailedMessages: left processing attachments unchanged (within staleness window)', [
                    'inbox_message_id' => $messageId,
                    'count' => $recentProcessingCount,
                    'staleness_minutes' => $stalenessMinutes,
                ]);
            }

            $this->enqueueParseJobsForInboxMessage($message->fresh(['attachments']));
        }

        return $messages->count();
    }

    /**
     * @return array{processed: int, failed: int, skipped: int}
     */
    public function attachmentTerminalCountsForScope(string $scope): array
    {
        $rows = InboxAttachment::query()
            ->where('scope', $scope)
            ->whereIn('processing_status', [
                InboxAttachmentProcessingStatus::Processed->value,
                InboxAttachmentProcessingStatus::Failed->value,
                InboxAttachmentProcessingStatus::Skipped->value,
            ])
            ->selectRaw('processing_status, COUNT(*) as aggregate')
            ->groupBy('processing_status')
            ->pluck('aggregate', 'processing_status');

        return [
            'processed' => (int) ($rows[InboxAttachmentProcessingStatus::Processed->value] ?? 0),
            'failed' => (int) ($rows[InboxAttachmentProcessingStatus::Failed->value] ?? 0),
            'skipped' => (int) ($rows[InboxAttachmentProcessingStatus::Skipped->value] ?? 0),
        ];
    }

    /**
     * @return array{processed: int, failed: int, skipped: int}
     */
    public function attachmentTerminalCountsForMessage(InboxMessage $message): array
    {
        $rows = $message->attachments()
            ->whereIn('processing_status', [
                InboxAttachmentProcessingStatus::Processed->value,
                InboxAttachmentProcessingStatus::Failed->value,
                InboxAttachmentProcessingStatus::Skipped->value,
            ])
            ->selectRaw('processing_status, COUNT(*) as aggregate')
            ->groupBy('processing_status')
            ->pluck('aggregate', 'processing_status');

        return [
            'processed' => (int) ($rows[InboxAttachmentProcessingStatus::Processed->value] ?? 0),
            'failed' => (int) ($rows[InboxAttachmentProcessingStatus::Failed->value] ?? 0),
            'skipped' => (int) ($rows[InboxAttachmentProcessingStatus::Skipped->value] ?? 0),
        ];
    }

    /**
     * Rows keyed by status: [messages count, attachments count]. Message `processed` aligns with attachment `processed`.
     *
     * @return array<string, array{0: int, 1: int}>
     */
    public function inboxStatusBreakdown(string $scope): array
    {
        $statuses = array_map(
            static fn (InboxMessageProcessingStatus $s): string => $s->value,
            InboxMessageProcessingStatus::cases()
        );

        $msgCounts = InboxMessage::query()
            ->where('scope', $scope)
            ->selectRaw('processing_status, COUNT(*) as aggregate')
            ->groupBy('processing_status')
            ->pluck('aggregate', 'processing_status');

        $attByRaw = InboxAttachment::query()
            ->where('scope', $scope)
            ->selectRaw('processing_status, COUNT(*) as aggregate')
            ->groupBy('processing_status')
            ->pluck('aggregate', 'processing_status');

        $rows = [];

        foreach ($statuses as $status) {
            $messages = (int) ($msgCounts[$status] ?? 0);

            $attachments = match ($status) {
                InboxMessageProcessingStatus::New->value => (int) ($attByRaw[InboxAttachmentProcessingStatus::New->value] ?? 0)
                    + (int) ($attByRaw[InboxAttachmentProcessingStatus::Processing->value] ?? 0),
                InboxMessageProcessingStatus::Read->value => 0,
                InboxMessageProcessingStatus::Processed->value => (int) ($attByRaw[InboxAttachmentProcessingStatus::Processed->value] ?? 0),
                InboxMessageProcessingStatus::PartiallyFailed->value => (int) ($attByRaw[InboxAttachmentProcessingStatus::Failed->value] ?? 0),
                InboxMessageProcessingStatus::Failed->value => (int) ($attByRaw[InboxAttachmentProcessingStatus::Failed->value] ?? 0),
                InboxMessageProcessingStatus::Skipped->value => (int) ($attByRaw[InboxAttachmentProcessingStatus::Skipped->value] ?? 0),
            };

            $rows[$status] = [$messages, $attachments];
        }

        return $rows;
    }

    public function latestReceivedAtForScope(string $scope): ?Carbon
    {
        $value = InboxMessage::query()
            ->where('scope', $scope)
            ->whereNotNull('received_at')
            ->max('received_at');

        if ($value === null) {
            return null;
        }

        return Carbon::parse($value);
    }

    public function latestProcessedAtForScope(string $scope): ?Carbon
    {
        $value = InboxMessage::query()
            ->where('scope', $scope)
            ->whereNotNull('processed_at')
            ->max('processed_at');

        if ($value === null) {
            return null;
        }

        return Carbon::parse($value);
    }

    /**
     * Resolves an existing row when a unique constraint fires during delta ingest (test seam for diagnostic failures).
     */
    protected function findCollidingInboxMessageForUniqueViolation(string $scope, string $externalId, ?string $internetMessageIdForLog): ?InboxMessage
    {
        return InboxMessage::query()
            ->where('scope', $scope)
            ->where(function ($query) use ($externalId, $internetMessageIdForLog): void {
                $query->where('external_id', $externalId);

                if ($internetMessageIdForLog !== null && $internetMessageIdForLog !== '') {
                    $query->orWhere('message_id', $internetMessageIdForLog);
                }
            })
            ->first();
    }

    protected function createInboxMessageFromGraphMessage(Message $graphMessage, string $scope): ?InboxMessage
    {
        $externalId = $graphMessage->getId();
        if ($externalId === null || $externalId === '') {
            return null;
        }

        $from = $graphMessage->getFrom()?->getEmailAddress();
        $toRecipients = $graphMessage->getToRecipients() ?? [];
        $firstRecipient = $toRecipients[0] ?? null;
        $firstTo = $firstRecipient?->getEmailAddress();
        $body = $graphMessage->getBody();

        $headers = collect($graphMessage->getInternetMessageHeaders() ?? [])
            ->mapWithKeys(function ($h): array {
                $name = $h->getName();
                if ($name === null || $name === '') {
                    return [];
                }

                return [$name => $h->getValue()];
            })
            ->toArray();

        $contentType = $body?->getContentType();

        return InboxMessage::create([
            'scope' => $scope,
            'channel' => 'email',
            'external_id' => $graphMessage->getId(),
            'message_id' => $graphMessage->getInternetMessageId(),
            'from_email' => $from?->getAddress(),
            'from_name' => $from?->getName(),
            'to_email' => $firstTo?->getAddress(),
            'to_name' => $firstTo?->getName(),
            'subject' => $graphMessage->getSubject(),
            'received_at' => $graphMessage->getReceivedDateTime(),
            'raw_headers' => $headers !== [] ? $headers : null,
            'raw_body_text' => ($contentType !== null && $contentType->value() === BodyType::TEXT)
                ? $body?->getContent()
                : null,
            'raw_body_html' => ($contentType !== null && $contentType->value() === BodyType::HTML)
                ? $body?->getContent()
                : null,
            'has_attachments' => $graphMessage->getHasAttachments() ?? false,
            'processing_status' => InboxMessageProcessingStatus::New->value,
        ]);
    }
}
