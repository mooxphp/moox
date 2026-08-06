<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Facades\Log;
use Moox\Company\Models\Company;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Models\EbillingDocument;

final class EBillingFormatResolver
{
    public function __construct(
        private FormatRegistry $registry,
    ) {
    }

    /**
     * Resolve the format for generation. Once an artifact has been generated
     * (xml_storage_path is set), the format is frozen — retries use the same format.
     *
     * Preference chain: customer column → company.data → config.
     */
    public function resolveForGeneration(EbillingDocument $document): string
    {
        if ($this->isFrozen($document)) {
            return (string) $document->format;
        }

        $preferred = $this->preferredFormat($document);

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
     * Whether the human-readable XRechnung copy PDF should be attached to outbound mail.
     * The copy is always produced and downloadable; this only gates the mail attachment.
     *
     * Preference chain: customer column → company.data → config (default true).
     */
    public function resolveSendVisualCopy(EbillingDocument $document): bool
    {
        $customer = $this->resolveCustomer($document);

        if ($customer !== null && $customer->send_visual_copy !== null) {
            return (bool) $customer->send_visual_copy;
        }

        $company = $document->company ?? $this->matchCompanyFromBillData($document);

        if ($company !== null) {
            $data = $company->data;

            if (is_array($data) && array_key_exists('send_visual_copy', $data)) {
                return (bool) $data['send_visual_copy'];
            }
        }

        return (bool) config('e-billing.send_visual_copy', true);
    }

    /**
     * A document is frozen when generation has already produced an artifact.
     */
    private function isFrozen(EbillingDocument $document): bool
    {
        return is_string($document->xml_storage_path) && $document->xml_storage_path !== '';
    }

    private function preferredFormat(EbillingDocument $document): ?string
    {
        $customer = $this->resolveCustomer($document);

        if ($customer !== null) {
            $preferred = $customer->preferred_ebilling_format;

            if (is_string($preferred) && $preferred !== '') {
                return $preferred;
            }
        }

        return $this->preferredFormatFromCompany($document);
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

    private function resolveCustomer(EbillingDocument $document): ?Customer
    {
        $billData = $document->bill_data;

        if (! is_array($billData)) {
            return null;
        }

        $number = trim((string) ($billData['customer_number'] ?? ''));

        if ($number === '') {
            return null;
        }

        return Customer::query()
            ->where('customer_number', $number)
            ->where('is_active', true)
            ->first();
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
            ->where('status', 'active')
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalised])
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
