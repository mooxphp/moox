<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\DB;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Events\InvoiceManuallyConfirmed;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Invoice\Models\Invoice;

final class ConfirmInvoiceAction
{
    /**
     * Confirms an invoice via human review on its linked {@see EbillingDocument}.
     * Idempotent: returns false if the document is not in a confirmable state.
     *
     * When other versions share the same number + document type, this invoice
     * becomes the current version; older versions remain stored with is_current=false.
     *
     * @return array{confirmed: bool, previous_current_count: int}
     */
    public function execute(Invoice $invoice): array
    {
        $document = $invoice->relationLoaded('ebillingDocument')
            ? $invoice->ebillingDocument
            : EbillingDocument::query()->where('invoice_id', $invoice->id)->first();

        if (! $document instanceof EbillingDocument) {
            return ['confirmed' => false, 'previous_current_count' => 0];
        }

        $status = $document->review_status;
        if (! $status instanceof InvoiceProcessingStatus) {
            $raw = $document->getAttributes()['review_status'] ?? null;
            $status = is_string($raw) ? InvoiceProcessingStatus::tryFrom($raw) : null;
        }

        if ($status !== InvoiceProcessingStatus::DbValidated) {
            return ['confirmed' => false, 'previous_current_count' => 0];
        }

        if ($document->needsHumanReview()) {
            return ['confirmed' => false, 'previous_current_count' => 0];
        }

        $previousCurrentCount = 0;

        DB::transaction(function () use ($invoice, $document, &$previousCurrentCount): void {
            $document->transitionTo(InvoiceProcessingStatus::HumanConfirmed);

            $previousCurrentCount = $this->countOtherCurrentVersions($invoice);
            $invoice->makeCurrentVersion();
        });

        event(new InvoiceManuallyConfirmed(
            document: $document->fresh() ?? $document,
            confirmedBy: auth()->user()?->name,
            wasAutoValidatedFirst: false,
        ));

        $fresh = $document->fresh();
        if ($fresh instanceof EbillingDocument) {
            app(TryAutoApproveDocumentAction::class)->execute($fresh);
        }

        return ['confirmed' => true, 'previous_current_count' => $previousCurrentCount];
    }

    private function countOtherCurrentVersions(Invoice $invoice): int
    {
        $number = $invoice->invoice_number;
        $type = $invoice->document_type;

        if (! is_string($number) || trim($number) === '' || $type === null || $type === '') {
            return 0;
        }

        return Invoice::query()
            ->where('invoice_number', $number)
            ->where('document_type', $type)
            ->where('is_current', true)
            ->whereKeyNot($invoice->getKey())
            ->count();
    }
}
