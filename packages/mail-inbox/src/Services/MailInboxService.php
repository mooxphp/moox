<?php

declare(strict_types=1);

namespace Moox\MailInbox\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Moox\MailInbox\DeltaPersistResult;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\InboxDriverManager;
use Moox\MailInbox\InboxMessageDto;
use Moox\MailInbox\Jobs\ParsePdfJob;
use Moox\MailInbox\Jobs\StoreAttachmentsJob;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;
use Throwable;

class MailInboxService
{
    public function __construct(
        private InboxDriverManager $drivers,
    ) {
    }

    /**
     * Persist driver DTOs for a mailbox scope (dual-key dedup; attachments listed in StoreAttachmentsJob).
     *
     * @param  array<int, InboxMessageDto>  $messages
     */
    public function persistMessages(array $messages, string $scope): DeltaPersistResult
    {
        $persisted = 0;
        $skippedKnown = 0;
        $skippedNoAttachments = 0;

        foreach ($messages as $dto) {
            if (! $dto instanceof InboxMessageDto) {
                continue;
            }

            $externalId = $dto->externalId;
            if ($externalId === '') {
                Log::channel('mail-inbox')->error('[MailInbox] Skipping message with empty external id during persist');

                continue;
            }

            if (! $dto->hasAttachments) {
                $skippedNoAttachments++;

                continue;
            }

            $internetId = $dto->messageId;
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
                            // Migrate volatile → immutable as the driver re-delivers this mail.
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

                    Log::channel('mail-inbox')->debug(
                        '[MailInbox] Driver returned known message, skipping (pre-check)',
                        [
                            'external_id' => $externalId,
                            'message_id' => $internetId,
                            'scope' => $scope,
                        ],
                    );
                    $skippedKnown++;

                    continue;
                }
            } else {
                Log::channel('mail-inbox')->warning(
                    '[MailInbox] Message missing messageId, falling back to external_id for dedup',
                    [
                        'external_id' => $externalId,
                        'scope' => $scope,
                    ],
                );

                $existsAlready = InboxMessage::query()
                    ->where('scope', $scope)
                    ->where('external_id', $externalId)
                    ->exists();

                if ($existsAlready) {
                    Log::channel('mail-inbox')->debug(
                        '[MailInbox] Driver returned known message, skipping (pre-check)',
                        [
                            'external_id' => $externalId,
                            'message_id' => null,
                            'scope' => $scope,
                        ],
                    );
                    $skippedKnown++;

                    continue;
                }
            }

            try {
                $row = $this->createInboxMessageFromDto($dto, $scope);

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
                } catch (Throwable $diagnosticError) {
                    $payload['db_match'] = 'diagnostic_query_failed';
                    $payload['diagnostic_error'] = $diagnosticError::class.': '.$diagnosticError->getMessage();
                }

                Log::channel('mail-inbox')->info(
                    '[MailInbox] Persist race condition caught by unique constraint, skipping',
                    $payload,
                );
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
            $this->trySettle($message->external_id, SettlementOutcome::Failed, $message->id, $message->scope);

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
                $this->trySettle($message->external_id, SettlementOutcome::Failed, $message->id, $message->scope);
            } else {
                $message->error_message = null;
                $message->markAsProcessed();
                $this->trySettle($message->external_id, SettlementOutcome::Processed, $message->id, $message->scope);
            }

            return;
        }

        if ($allPdfs->isEmpty()) {
            $message->markAsProcessed();
            $this->trySettle($message->external_id, SettlementOutcome::Processed, $message->id, $message->scope);

            return;
        }

        if ($hasFailed) {
            $message->markAsFailed('One or more attachments failed processing');
            $this->trySettle($message->external_id, SettlementOutcome::Failed, $message->id, $message->scope);

            return;
        }

        if ($allPdfs->every(
            fn (InboxAttachment $a): bool => $a->processing_status === InboxAttachmentProcessingStatus::Processed->value
        )) {
            $message->markAsProcessed();
            $this->trySettle($message->external_id, SettlementOutcome::Processed, $message->id, $message->scope);
        }
    }

    private function trySettle(
        ?string $externalId,
        SettlementOutcome $outcome,
        ?int $inboxMessageId = null,
        ?string $scope = null,
    ): void {
        if ($externalId === null || $externalId === '') {
            return;
        }

        $mailbox = $scope !== null && $scope !== '' ? $scope : 'default';

        try {
            $this->drivers->mailbox($mailbox)->settle($externalId, $outcome);
        } catch (Throwable $e) {
            Log::channel('mail-inbox')->error('[MailInbox] Settlement failed', [
                'exception' => $e,
                'external_id' => $externalId,
                'inbox_message_id' => $inboxMessageId,
                'outcome' => $outcome->value,
                'scope' => $mailbox,
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

        foreach ($messages as $message) {
            $this->retryFailedMessage($message);
        }

        return $messages->count();
    }

    public function retryFailedMessage(InboxMessage $message): void
    {
        $message->loadMissing('attachments');

        $stalenessMinutes = max(1, (int) config('mail-inbox.retry_staleness_minutes', 30));
        $stalenessThreshold = now()->subMinutes($stalenessMinutes);
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
            Log::channel('mail-inbox')->warning(
                '[MailInbox] retryFailedMessage: left processing attachments unchanged'
                    .' (within staleness window)',
                [
                    'inbox_message_id' => $messageId,
                    'count' => $recentProcessingCount,
                    'staleness_minutes' => $stalenessMinutes,
                ],
            );
        }

        $this->enqueueParseJobsForInboxMessage($message->fresh(['attachments']));
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

    protected function createInboxMessageFromDto(InboxMessageDto $dto, string $scope): ?InboxMessage
    {
        if ($dto->externalId === '') {
            return null;
        }

        $row = InboxMessage::create([
            'scope' => $scope,
            'channel' => 'email',
            'external_id' => $dto->externalId,
            'message_id' => $dto->messageId,
            'from_email' => $dto->from !== '' ? $dto->from : null,
            'from_name' => $dto->fromName,
            'to_email' => $dto->toEmail,
            'to_name' => $dto->toName,
            'subject' => $dto->subject !== '' ? $dto->subject : null,
            'received_at' => $dto->receivedAt,
            'raw_headers' => null,
            'raw_body_text' => $dto->bodyText,
            'raw_body_html' => $dto->bodyHtml,
            'has_attachments' => $dto->hasAttachments,
            'processing_status' => InboxMessageProcessingStatus::New->value,
        ]);

        return $row;
    }
}
