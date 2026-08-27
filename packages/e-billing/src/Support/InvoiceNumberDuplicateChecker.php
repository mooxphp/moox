<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Collection;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Invoice\Models\Invoice;

/**
 * Detects an already-known invoice / credit-note number (billing#13 / #21 / #24).
 *
 * Same number under the same document type always flags review when the source
 * PDF content differs. Byte-identical source PDFs with the same number are not
 * new versions — they are discarded before a second invoice row is created.
 * Soft-deleted invoices are ignored by the default SoftDeletes scope.
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

    /**
     * Same number + type + source PDF hash as an already stored document.
     * Both hashes must be non-empty; missing hashes never count as identical.
     */
    public function findIdenticalContentDuplicate(
        string $invoiceNumber,
        string $documentType,
        string $sourceContentHash,
        ?string $exceptDocumentId = null,
    ): ?Invoice {
        if (trim($invoiceNumber) === '' || trim($documentType) === '' || trim($sourceContentHash) === '') {
            return null;
        }

        $query = EbillingDocument::query()
            ->where('source_content_hash', $sourceContentHash)
            ->whereNotNull('invoice_id')
            ->whereHas('invoice', function ($invoiceQuery) use ($invoiceNumber, $documentType): void {
                $invoiceQuery
                    ->where('invoice_number', $invoiceNumber)
                    ->where('document_type', $documentType);
            })
            ->with('invoice')
            ->orderBy('created_at')
            ->orderBy('id');

        if (is_string($exceptDocumentId) && $exceptDocumentId !== '') {
            $query->whereKeyNot($exceptDocumentId);
        }

        $document = $query->first();

        if (! $document instanceof EbillingDocument) {
            return null;
        }

        $invoice = $document->invoice;

        return $invoice instanceof Invoice ? $invoice : null;
    }
}
