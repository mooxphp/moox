<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\DocumentClassification;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceAllowanceCharge;

/**
 * A reviewer decides which kind of document this is, e.g. credit note (381) or corrected invoice (384),
 * ADR 0011. The field changes are audited by moox/audit like any invoice update; the act itself is logged
 * as its own `document_classified` activity so it is never read as a value correction: the source document
 * reads the same either way. When the two types carry opposite signs, every document and line amount is
 * negated with it. Regeneration and re-validation are left to the review workspace's leave-edit path.
 */
final class ClassifyDocumentTypeAction
{
    public const ACTIVITY_EVENT = DocumentClassification::ACTIVITY_EVENT;

    /**
     * @return bool false when the document already has that type
     */
    public function execute(EbillingDocument $document, string $documentType): bool
    {
        if (auth()->user() === null) {
            throw new InvalidArgumentException('An authenticated actor is required to classify a document.');
        }

        if ($document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Approved) {
            throw new InvalidArgumentException('An approved document cannot be reclassified.');
        }

        $invoice = $document->invoice;
        if (! $invoice instanceof Invoice) {
            throw new InvalidArgumentException("Document #{$document->id} has no linked invoice.");
        }

        $currentType = (string) $invoice->document_type;

        if (! DocumentClassification::isClassificationType($currentType)
            || ! DocumentClassification::isClassificationType($documentType)) {
            throw new InvalidArgumentException(
                "Document type {$currentType} cannot be reclassified as {$documentType}.",
            );
        }

        if ($currentType === $documentType) {
            return false;
        }

        DB::transaction(function () use ($document, $invoice, $currentType, $documentType): void {
            $amountsNegated = DocumentClassification::signsDiffer($currentType, $documentType);
            if ($amountsNegated) {
                $this->negateAmounts($invoice);
            }

            $invoice->document_type = $documentType;
            $invoice->save();

            DocumentClassification::recordActivity($document, $currentType, $documentType, $amountsNegated, 'review');
        });

        return true;
    }

    /**
     * Sign flip on the persisted invoice. Keep in step with the flip on parsed data,
     * {@see DocumentClassification::negateBillData()}: both must cover every amount.
     */
    private function negateAmounts(Invoice $invoice): void
    {
        $invoice->net_total = -(float) $invoice->net_total;
        $invoice->vat_amount = -(float) $invoice->vat_amount;
        $invoice->gross_total = -(float) $invoice->gross_total;

        $invoice->load(['lines.allowanceCharges', 'allowanceCharges']);

        foreach ($invoice->lines as $line) {
            $line->quantity = -(float) $line->quantity;
            $line->line_total = -(float) $line->line_total;
            $line->save();

            $line->allowanceCharges->each(fn (InvoiceAllowanceCharge $charge) => $this->negateCharge($charge));
        }

        $invoice->allowanceCharges->each(fn (InvoiceAllowanceCharge $charge) => $this->negateCharge($charge));
    }

    private function negateCharge(InvoiceAllowanceCharge $charge): void
    {
        $charge->amount = -(float) $charge->amount;
        $charge->base_amount = $charge->base_amount !== null ? -(float) $charge->base_amount : null;
        $charge->save();
    }
}
