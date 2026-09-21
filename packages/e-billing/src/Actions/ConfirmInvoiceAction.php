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
    public function __construct(
        private readonly TryAutoApproveDocumentAction $tryAutoApprove,
    ) {
    }

    /**
     * Confirms an invoice via human review on its linked {@see EbillingDocument}.
     * Idempotent: returns confirmed=false if the document is not in a confirmable state.
     *
     * Hard-block (while db_validated): configured must fields that are missing.
     * needs_review and missing should findings do not refuse confirm — see ADR 0005.
     * needsHumanReview() stays unchanged for queue / auto-approve / dispatch.
     *
     * When other versions share the same number + document type, this invoice
     * becomes the current version; older versions remain stored with is_current=false.
     *
     * @return array{confirmed: bool, previous_current_count: int, missing_must_fields: list<string>}
     */
    public function execute(Invoice $invoice): array
    {
        $document = $invoice->relationLoaded('ebillingDocument')
            ? $invoice->ebillingDocument
            : EbillingDocument::query()->where('invoice_id', $invoice->id)->first();

        if (! $document instanceof EbillingDocument) {
            return $this->failure();
        }

        $status = $document->review_status;
        if (! $status instanceof InvoiceProcessingStatus) {
            $raw = $document->getAttributes()['review_status'] ?? null;
            $status = is_string($raw) ? InvoiceProcessingStatus::tryFrom($raw) : null;
        }

        if ($status !== InvoiceProcessingStatus::DbValidated) {
            return $this->failure();
        }

        $missingMustFields = EbillingDocument::missingMustFields(
            is_array($document->field_validations) ? $document->field_validations : null,
        );
        if ($missingMustFields !== []) {
            return $this->failure($missingMustFields);
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
            $this->tryAutoApprove->execute($fresh);
        }

        return [
            'confirmed' => true,
            'previous_current_count' => $previousCurrentCount,
            'missing_must_fields' => [],
        ];
    }

    /**
     * @param  list<string>  $missingMustFields
     * @return array{confirmed: bool, previous_current_count: int, missing_must_fields: list<string>}
     */
    private function failure(array $missingMustFields = []): array
    {
        return [
            'confirmed' => false,
            'previous_current_count' => 0,
            'missing_must_fields' => $missingMustFields,
        ];
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
