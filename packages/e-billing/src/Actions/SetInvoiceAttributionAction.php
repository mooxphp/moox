<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use InvalidArgumentException;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Enums\AttributionSource;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\CustomerMatcher;

/**
 * Operator-set customer attribution. Survives automatic rematch.
 */
final class SetInvoiceAttributionAction
{
    public function execute(EbillingDocument $document, ?string $customerId): void
    {
        if ($customerId === null || $customerId === '') {
            $document->customer_id = null;
            $document->company_id = null;
            $document->attribution_source = null;
            $this->invalidateConfirmationIfNeeded($document);
            $document->save();

            return;
        }

        $customer = Customer::query()->withTrashed()->find($customerId);

        if (! $customer instanceof Customer) {
            throw new InvalidArgumentException("Customer [{$customerId}] was not found.");
        }

        $document->customer_id = (string) $customer->getKey();
        $document->company_id = (new CustomerMatcher)->resolveCompanyId($customer);
        $document->attribution_source = AttributionSource::Manual;
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
