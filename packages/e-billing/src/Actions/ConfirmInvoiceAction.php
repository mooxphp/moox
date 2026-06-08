<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Events\InvoiceManuallyConfirmed;
use Moox\Invoice\Models\Invoice;
use Moox\EBilling\Models\EbillingDocument;

final class ConfirmInvoiceAction
{
    /**
     * Confirms an invoice via human review on its linked {@see EbillingDocument}.
     * Idempotent: returns false if the document is not in a confirmable state.
     */
    public function execute(Invoice $invoice): bool // Extend Invoice in your host app if needed
    {
        $document = $invoice->relationLoaded('ebillingDocument')
            ? $invoice->ebillingDocument
            : EbillingDocument::query()->where('invoice_id', $invoice->id)->first();

        if (! $document instanceof EbillingDocument) {
            return false;
        }

        $status = $document->review_status;
        if (! $status instanceof InvoiceProcessingStatus) {
            $raw = $document->getAttributes()['review_status'] ?? null;
            $status = is_string($raw) ? InvoiceProcessingStatus::tryFrom($raw) : null;
        }

        if ($status !== InvoiceProcessingStatus::DbValidated) {
            return false;
        }

        $document->transitionTo(InvoiceProcessingStatus::HumanConfirmed);

        event(new InvoiceManuallyConfirmed(
            document: $document,
            confirmedBy: auth()->user()?->name,
            wasAutoValidatedFirst: false,
        ));

        return true;
    }
}
