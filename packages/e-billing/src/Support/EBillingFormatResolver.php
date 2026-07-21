<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Facades\Log;
use Moox\Company\Models\Company;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InvoiceFieldValidator;

final class EBillingFormatResolver
{
    public function __construct(
        private FormatRegistry $registry,
    ) {}

    /**
     * Resolve the format for generation. Once an artifact has been generated
     * (xml_storage_path is set), the format is frozen — retries use the same format.
     */
    public function resolveForGeneration(EbillingDocument $document): string
    {
        if ($this->isFrozen($document)) {
            return (string) $document->format;
        }

        $preferred = $this->preferredFormatFromCompany($document);

        if ($preferred !== null && $this->registry->has($preferred)) {
            return $preferred;
        }

        if ($preferred !== null) {
            Log::warning('[EBilling] Unknown preferred_ebilling_format, falling back to default', [
                'preferred' => $preferred,
                'document_id' => $document->getKey(),
            ]);
        }

        return (string) config('e-billing.default_format', 'zugferd');
    }

    /**
     * A document is frozen when generation has already produced an artifact.
     */
    private function isFrozen(EbillingDocument $document): bool
    {
        return is_string($document->xml_storage_path) && $document->xml_storage_path !== '';
    }

    private function preferredFormatFromCompany(EbillingDocument $document): ?string
    {
        $company = $document->company ?? $this->matchCompanyFromBillData($document);

        if ($company === null) {
            return null;
        }

        $data = $company->data;
        $preferred = is_array($data) ? ($data['preferred_ebilling_format'] ?? null) : null;

        return is_string($preferred) && $preferred !== '' ? $preferred : null;
    }

    /**
     * Same loose name-match as {@see InvoiceFieldValidator::resolveCompanyMatch()}.
     */
    private function matchCompanyFromBillData(EbillingDocument $document): ?Company
    {
        $billData = $document->bill_data;

        if (! is_array($billData)) {
            return null;
        }

        $name = trim((string) ($billData['customer_name'] ?? ''));

        if ($name === '') {
            return null;
        }

        $normalised = mb_strtolower(trim($name));

        $matches = Company::query()
            ->where('company_type', 'customer')
            ->where('is_active', true)
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalised])
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
