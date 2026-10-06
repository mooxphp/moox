<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\DB;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Approval\DocumentEditGuard;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Jobs\GenerateArtifactJob;
use Moox\EBilling\Models\EbillingDocument;
use Throwable;

/**
 * Re-runs field validation and artifact generation from the stored Invoice, for validation or emission
 * changes that must reach documents already imported. Unlike {@see ReparseDocumentAction} the Invoice and
 * any review corrections stay; the path is the one {@see LeaveEditAction} takes.
 */
final class RevalidateDocumentAction
{
    public const ACTIVITY_EVENT = 'document_revalidated';

    public function __construct(
        private readonly DocumentEditGuard $editGuard,
    ) {
    }

    /**
     * @return ?string refusal reason, null when generation was queued
     */
    public function execute(EbillingDocument $document, bool $hold = false): ?string
    {
        $refusal = $this->refusal($document);
        if ($refusal !== null) {
            return $refusal;
        }

        $previousReviewStatus = $document->review_status?->value;

        DB::transaction(function () use ($document, $hold): void {
            if ($hold) {
                $document->holdForApproval();
            }

            // Sanctioned backward move: GenerateArtifactJob refills field validations and refuses
            // validated / human_confirmed.
            $document->review_status = InvoiceProcessingStatus::ParserCreated;
            $document->gateway_status = EBillingAttachmentProcessingStatus::Generating;
            $document->save();
        });

        $this->recordActivity($document, $previousReviewStatus, $hold);

        try {
            GenerateArtifactJob::dispatch((string) $document->getKey());
        } catch (Throwable $exception) {
            // Never leave the document in `generating` without a job that would end it.
            $document->gateway_status = EBillingAttachmentProcessingStatus::GenerationFailed;
            $document->save();

            throw $exception;
        }

        return null;
    }

    /**
     * Reason key when the document must not be revalidated, null when it qualifies.
     */
    public function refusal(EbillingDocument $document): ?string
    {
        $approval = $document->resolveApprovalStatusEnum();

        return match (true) {
            $document->invoice_id === null => 'no_invoice',
            $document->review_status === InvoiceProcessingStatus::HumanConfirmed => 'human_confirmed',
            $approval !== null && $approval !== DocumentApprovalStatus::Pending => 'approval_decided',
            $this->editGuard->isPipelineRunning($document) => 'pipeline_running',
            $document->deliveryAttempts()->exists() => 'delivered',
            default => null,
        };
    }

    private function recordActivity(EbillingDocument $document, ?string $previousReviewStatus, bool $hold): void
    {
        if (! class_exists(MooxActivityLogger::class)) {
            return;
        }

        MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
            'event' => self::ACTIVITY_EVENT,
            'entry_type' => 'log',
            'subject' => $document,
            'properties' => [
                'review_status' => $previousReviewStatus,
                'held' => $hold,
            ],
        ]);
    }
}
