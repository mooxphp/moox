<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\DB;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Data\ReparseOutcome;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Jobs\GenerateArtifactJob;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\EBilling;
use Moox\EBilling\Support\DocumentClassification;
use Throwable;

/**
 * Re-runs the parser on a document's source PDF and regenerates its artifact, for parser fixes that must
 * reach documents already imported. Generation maps `bill_data` only while the document has no Invoice
 * (ADR 0013), so the draft Invoice is soft-deleted and unlinked first.
 *
 * Only an untouched machine draft qualifies: once a person reviewed, confirmed or approved it, or it was
 * sent, the stored Invoice is the record and a reparse would overwrite it.
 */
final class ReparseDocumentAction
{
    public const ACTIVITY_EVENT = 'document_reparsed';

    public function __construct(
        private readonly EBilling $eBilling,
    ) {
    }

    public function execute(EbillingDocument $document): ReparseOutcome
    {
        $refusal = $this->refusal($document);
        if ($refusal !== null) {
            return ReparseOutcome::refused($refusal);
        }

        $parsed = DocumentClassification::applyDeclaredTypeOnParse(
            $document,
            $this->eBilling->parseInvoiceFromPdf($document->sourceFullPath()),
            'reparsing',
        );

        $oldInvoice = $document->invoice;
        $oldNetTotal = $oldInvoice?->net_total;
        $oldLineTotal = $oldInvoice?->lines->sum(fn ($line): float => (float) $line->line_total);

        DB::transaction(function () use ($document, $parsed): void {
            $document->invoice?->delete();

            $document->forceFill([
                'invoice_id' => null,
                'bill_data' => $parsed->toArray(),
                'field_validations' => null,
                'validation_score' => null,
                'review_status' => InvoiceProcessingStatus::ParserCreated,
                'gateway_status' => EBillingAttachmentProcessingStatus::Generating,
            ])->save();
        });

        $outcome = ReparseOutcome::reparsed(
            oldNetTotal: $oldNetTotal !== null ? self::amount((float) $oldNetTotal) : null,
            newNetTotal: self::amount($parsed->netTotal),
            oldLineTotal: $oldLineTotal !== null ? self::amount((float) $oldLineTotal) : null,
            newLineTotal: self::amount(array_sum(array_map(fn ($line): float => $line->lineTotal, $parsed->lines))),
        );
        $this->recordActivity($document, $outcome);

        try {
            GenerateArtifactJob::dispatch((string) $document->getKey());
        } catch (Throwable $exception) {
            // Never leave the document in `generating` without a job that would end it.
            $document->gateway_status = EBillingAttachmentProcessingStatus::GenerationFailed;
            $document->save();

            throw $exception;
        }

        return $outcome;
    }

    private static function amount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * Reason key when the document must not be reparsed, null when it qualifies.
     */
    public function refusal(EbillingDocument $document): ?string
    {
        return match (true) {
            $document->review_status === InvoiceProcessingStatus::HumanConfirmed => 'human_confirmed',
            $document->resolveApprovalStatusEnum() !== null => 'approval_started',
            $document->review_changed_at !== null => 'review_edited',
            $document->deliveryAttempts()->exists() => 'delivered',
            default => null,
        };
    }

    private function recordActivity(EbillingDocument $document, ReparseOutcome $outcome): void
    {
        if (! class_exists(MooxActivityLogger::class)) {
            return;
        }

        MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
            'event' => self::ACTIVITY_EVENT,
            'entry_type' => 'log',
            'subject' => $document,
            'properties' => [
                'old_net_total' => $outcome->oldNetTotal,
                'new_net_total' => $outcome->newNetTotal,
                'old_line_total' => $outcome->oldLineTotal,
                'new_line_total' => $outcome->newLineTotal,
            ],
        ]);
    }
}
