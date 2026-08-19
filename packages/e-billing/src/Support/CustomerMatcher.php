<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Company\Models\Company;
use Moox\Customer\Models\Customer;
use Moox\Customer\Models\CustomerAssignment;
use Moox\EBilling\Services\InvoiceFieldValidator;

/**
 * Resolves a {@see Customer} from a buyer identifier (invoice customer_number).
 *
 * Used by {@see InvoiceFieldValidator} and {@see EBillingFormatResolver}.
 */
final class CustomerMatcher
{
    public function match(?string $identifier): ?Customer
    {
        $normalized = $this->normalizeIdentifier($identifier ?? '');

        if ($normalized === '') {
            return null;
        }

        $matches = Customer::query()
            ->withTrashed()
            ->whereRaw('UPPER(TRIM(customer_number)) = ?', [$normalized])
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * Derive company id from the customer: exactly one company assignment, else null.
     */
    public function resolveCompanyId(Customer $customer): ?string
    {
        $companyMorph = (new Company)->getMorphClass();

        $companyIds = CustomerAssignment::query()
            ->where('customer_id', $customer->getKey())
            ->where('assignable_type', $companyMorph)
            ->limit(2)
            ->pluck('assignable_id');

        if ($companyIds->count() !== 1) {
            return null;
        }

        $companyId = (string) $companyIds->first();

        return Company::query()->find($companyId) !== null ? $companyId : null;
    }

    /**
     * Soft-deleted, inactive, or missing/ambiguous company derivation ⇒ needs review.
     * Attribution is still kept; corroboration never clears it.
     */
    public function isReviewableMatch(Customer $customer, ?string $derivedCompanyId = null): bool
    {
        if ($customer->trashed() || $customer->is_active === false) {
            return true;
        }

        return $derivedCompanyId === null;
    }

    private function normalizeIdentifier(string $value): string
    {
        $trimmed = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return mb_strtoupper($trimmed);
    }
}
