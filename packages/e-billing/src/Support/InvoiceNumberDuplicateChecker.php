<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Collection;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Invoice\Models\Invoice;

/**
 * Detects an already-known invoice / credit-note number (billing#13 / #21 / #24).
 *
 * Same number under the same document type flags review when the source PDF
 * content differs. Byte-identical source PDFs with the same number are discarded
 * before a second invoice row is created. Soft-deleted invoices are ignored.
 *
 * Comparison scope is config `e-billing.duplicate_number.scope`:
 * `global` (default) or `issuer` (also seller VAT id / BT-31).
 */
final class InvoiceNumberDuplicateChecker
{
    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_ISSUER = 'issuer';

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

        $matches = $query->orderBy('created_at')->orderBy('id')->get();

        return $this->filterByIssuerScope(
            $matches,
            VatIdNormalizer::normalize($invoice->seller?->vat_id),
        );
    }

    public function isDuplicate(Invoice $invoice): bool
    {
        return $this->findDuplicate($invoice) instanceof Invoice;
    }

    /**
     * Same number + type + source PDF hash as an already stored document.
     * Both hashes must be non-empty; missing hashes never count as identical.
     * When scope is `issuer`, `$sellerVatId` narrows the match (blank buckets with blank).
     */
    public function findIdenticalContentDuplicate(
        string $invoiceNumber,
        string $documentType,
        string $sourceContentHash,
        ?string $exceptDocumentId = null,
        ?string $sellerVatId = null,
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

        $invoices = $query->get()
            ->map(fn (EbillingDocument $document): mixed => $document->invoice)
            ->filter(fn (mixed $invoice): bool => $invoice instanceof Invoice)
            ->values();

        /** @var Collection<int, Invoice> $invoices */
        $match = $this->filterByIssuerScope(
            $invoices,
            VatIdNormalizer::normalize($sellerVatId),
        )->first();

        return $match instanceof Invoice ? $match : null;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, Invoice>
     */
    private function filterByIssuerScope(Collection $invoices, ?string $normalizedSellerVatId): Collection
    {
        if ($this->comparisonScope() !== self::SCOPE_ISSUER) {
            return $invoices;
        }

        return $invoices
            ->filter(function (Invoice $invoice) use ($normalizedSellerVatId): bool {
                return VatIdNormalizer::normalize($invoice->seller?->vat_id) === $normalizedSellerVatId;
            })
            ->values();
    }

    private function comparisonScope(): string
    {
        $scope = config('e-billing.duplicate_number.scope', self::SCOPE_GLOBAL);

        return $scope === self::SCOPE_ISSUER ? self::SCOPE_ISSUER : self::SCOPE_GLOBAL;
    }
}
