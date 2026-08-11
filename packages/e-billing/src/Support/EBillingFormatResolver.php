<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Facades\Log;
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
     * Preference chain: customer column → config.
     */
    public function resolveForGeneration(EbillingDocument $document): string
    {
        if ($this->isFrozen($document)) {
            return (string) $document->format;
        }

        $preferred = $this->preferredFormatFromCustomer($document);

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
     * Preference chain: customer column → config (default true).
     */
    public function resolveSendVisualCopy(EbillingDocument $document): bool
    {
        $customer = $document->customer ?? $this->loadCustomer($document);

        if ($customer !== null && $customer->send_visual_copy !== null) {
            return (bool) $customer->send_visual_copy;
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

    private function preferredFormatFromCustomer(EbillingDocument $document): ?string
    {
        $customer = $this->resolveCustomer($document);

        if ($customer === null) {
            return null;
        }

        $preferred = $customer->preferred_ebilling_format;

        return is_string($preferred) && $preferred !== '' ? $preferred : null;
    }

    private function resolveCustomer(EbillingDocument $document): ?Customer
    {
        if ($document->customer_id !== null) {
            return Customer::query()
                ->withTrashed()
                ->find($document->customer_id);
        }

        $document->loadMissing('invoice');

        return (new CustomerMatcher)->match($document->invoice?->customer_number);
    }
}
