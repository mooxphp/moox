<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Approval\DocumentEditGuard;
use Moox\EBilling\Data\PartyReference;
use Moox\EBilling\Enums\AttributionSource;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Operator-set customer attribution: the customer as `buyer` and its contact as `buyer_contact`, both `manual`.
 * Survives automatic rematch.
 */
final class SetInvoiceAttributionAction
{
    public function __construct(
        private readonly InvalidateDocumentApprovalAction $invalidateApproval,
        private readonly DocumentEditGuard $editGuard,
        private readonly RecordAttributionAction $recordAttribution,
    ) {
    }

    public function execute(EbillingDocument $document, ?string $customerId): void
    {
        DB::transaction(function () use ($document, $customerId): void {
            // Under lock: leave-edit may have started the pipeline since the document was loaded.
            $locked = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
            $this->editGuard->assertPipelineIdle($locked);

            $this->attribute($locked, $customerId);
        });

        $document->refresh();
        $this->invalidateApproval->execute($document);
    }

    /**
     * A changed attribution is a review change (ADR 0004): leave-edit re-matches and regenerates after it.
     */
    private function attribute(EbillingDocument $document, ?string $customerId): void
    {
        $buyer = null;
        $contact = null;
        $source = null;

        if ($customerId !== null && $customerId !== '') {
            $customer = Customer::query()->withTrashed()->find($customerId);

            if (! $customer instanceof Customer) {
                throw new InvalidArgumentException("Customer [{$customerId}] was not found.");
            }

            $buyer = PartyReference::of($customer);
            $customerContact = $customer->getRelationValue('contact');
            $contact = $customerContact instanceof Model ? PartyReference::of($customerContact) : null;
            $source = AttributionSource::Manual;
        }

        if ($this->recordAttribution->executeBuyer($document, $buyer, $contact, $source)) {
            $document->forceFill(['review_changed_at' => now()]);
        }

        $this->invalidateConfirmationIfNeeded($document);
        $document->save();
    }

    /**
     * Changing identity after human confirmation / validation voids the attestation
     * so the document must be re-confirmed (customer_id is a visibility gate).
     */
    private function invalidateConfirmationIfNeeded(EbillingDocument $document): void
    {
        $status = $document->review_status;
        if (! $status instanceof InvoiceProcessingStatus) {
            $raw = $document->getAttributes()['review_status'] ?? null;
            $status = is_string($raw) ? InvoiceProcessingStatus::tryFrom($raw) : null;
        }

        if (in_array($status, [InvoiceProcessingStatus::HumanConfirmed, InvoiceProcessingStatus::Validated], true)) {
            $document->review_status = InvoiceProcessingStatus::DbValidated;
        }
    }
}
