<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Collection;
use Moox\Invoice\Models\Invoice;

/**
 * Detects an already-known invoice / credit-note number (billing#13 / #21 / #24).
 *
 * Same number under the same document type always flags review — content identity
 * does not matter; silent de-duplication on upload is explicitly out of scope.
 * Making a later upload the current Fassung happens on human confirm (versioning).
 */
final class InvoiceNumberDuplicateChecker
{
    public function findDuplicate(Invoice $invoice): ?Invoice
    {
        return $this->findDuplicates($invoice)->first();
    }

    /**
     * Other non-deleted invoices with the same number and document type.
     *
     * @return Collection<int, Invoice>
     */
    public function findDuplicates(Invoice $invoice): Collection
    {
        $number = $invoice->invoice_number;

        if (! is_string($number) || trim($number) === '') {
            return collect();
        }

        $query = Invoice::query()
            ->where('invoice_number', $number)
            ->where('document_type', $invoice->document_type);

        $key = $invoice->getKey();

        if ($key !== null && $key !== '') {
            $query->whereKeyNot($key);
        }

        return $query->orderBy('created_at')->orderBy('id')->get();
    }

    public function isDuplicate(Invoice $invoice): bool
    {
        return $this->findDuplicate($invoice) instanceof Invoice;
    }
}
