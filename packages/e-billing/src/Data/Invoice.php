<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\EBilling\Support\BillDataAllowanceChargeMapper;
use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdBankAccount;
use Moox\Zugferd\Contracts\ZugferdInvoice;

class Invoice implements ZugferdInvoice
{
    public function __construct(
        public string $invoiceNumber,
        public string $invoiceDate,
        public string $documentType = 'Rechnung',
        public ?string $dueDate = null,

        // Customer
        public string $customerNumber = '',
        public string $customerName = '',
        public ?Address $customerAddress = null,
        public ?string $customerVatId = null,
        public ?string $customerReference = null,

        public ?string $orderNumber = null,
        public ?string $orderDate = null,
        public ?Address $deliveryAddress = null,

        // Supplier
        public string $supplierName = '',
        public ?string $supplierVatId = null,
        public ?string $supplierTaxNumber = null,
        public ?Address $supplierAddress = null,
        public ?string $supplierNumber = null,
        public ?string $supplierPhone = null,
        public ?string $supplierEmail = null,

        /** @var array<int, array{bank_name: string, iban: string, bic: string, is_default?: bool, account_holder?: string}> */
        public array $supplierBankAccounts = [],

        // Agent & payment terms
        public ?string $agent = null,
        public ?string $paymentTerms = null,
        public ?string $pricingBasis = null,
        public ?string $paymentMeansCode = null,

        // Amounts
        public float $netTotal = 0,
        public float $vatRate = 19.00,
        public float $vatAmount = 0,
        public float $grossTotal = 0,
        public ?float $discountPercent = null,
        public ?float $discountAmount = null,
        public ?float $shippingCost = null,
        public ?float $minimumQuantitySurcharge = null,
        public ?float $freightFlatRate = null,
        public ?float $packagingCost = null,
        public ?string $shippingMethod = null,

        /** @var InvoiceLine[] */
        public array $lines = [],

        /** @var array<int, string> */
        public array $notes = [],

        // Currency
        public string $currency = 'EUR',
    ) {}

    /** @var list<ZugferdBankAccount> */
    public array $bankAccounts {
        get {
            $accounts = [];
            foreach ($this->supplierBankAccounts as $row) {
                if (is_array($row)) {
                    $accounts[] = BankAccount::fromArray($row);
                }
            }

            return $accounts;
        }
    }

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges {
        get {
            return BillDataAllowanceChargeMapper::fromHeaderScalars(
                $this->shippingCost,
                $this->packagingCost,
                $this->minimumQuantitySurcharge,
                $this->freightFlatRate,
                $this->discountAmount,
                $this->discountPercent,
            );
        }
    }

    /**
     * Factory: creates an Invoice and pulls supplier data from config.
     */
    public static function fromConfig(array $data): self
    {
        $supplier = config('e-billing.supplier');
        $customerName = is_string($data['customer_name'] ?? null) ? $data['customer_name'] : '';
        $customerAddress = Address::fromMixedWithParty($data['customer_address'] ?? null, $customerName);
        $billingCountry = self::normalizeCountry($data['billing_country'] ?? $data['country'] ?? null);

        // Legacy backfill: root 'billing_country'/'country' keys flow into customerAddress->country when missing.
        // customerAddress->country is the canonical DTO source of truth for buyer country.
        if ($customerAddress !== null && $customerAddress->country === null && $billingCountry !== null) {
            $customerAddress->country = $billingCountry;
        }

        $supplierAddress = Address::fromMixedWithParty($supplier['address'] ?? null, $supplier['name'] ?? '');
        if ($supplierAddress !== null
            && ($supplierAddress->country === null || trim((string) $supplierAddress->country) === '')
            && isset($supplier['country_code']) && is_string($supplier['country_code']) && trim($supplier['country_code']) !== '') {
            $supplierAddress->country = strtoupper(trim($supplier['country_code']));
        }

        $invoice = new self(
            invoiceNumber: $data['invoice_number'],
            invoiceDate: $data['invoice_date'],

            // Customer
            customerNumber: $data['customer_number'] ?? '',
            customerName: $customerName,
            customerAddress: $customerAddress,
            customerVatId: $data['customer_vat_id'] ?? null,
            customerReference: $data['customer_reference'] ?? null,

            // Supplier snapshot from config
            supplierName: $supplier['name'] ?? '',
            supplierVatId: $supplier['vat_id'] ?? null,
            supplierTaxNumber: $supplier['tax_number'] ?? null,
            supplierAddress: $supplierAddress,
            supplierPhone: $supplier['phone'] ?? null,
            supplierEmail: $supplier['email'] ?? null,

            // Bank accounts
            supplierBankAccounts: $supplier['bank_accounts'] ?? [],

            // Amounts
            netTotal: $data['net_total'] ?? 0,
            vatRate: $data['vat_rate'] ?? 19.0,
            vatAmount: $data['vat_amount'] ?? 0,
            grossTotal: $data['gross_total'] ?? 0,
        );

        $invoice->applyDefaultCustomerCountry();

        return $invoice;
    }

    /**
     * Validate that essential fields are present.
     */
    public function isValid(): bool
    {
        return $this->invoiceNumber !== ''
            && $this->invoiceDate !== ''
            && $this->grossTotal > 0;
    }

    /**
     * Reconstruct a DTO from {@see toArray()} output (e.g. persisted `bill_data` on an attachment).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $customerName = is_string($data['customer_name'] ?? null) ? $data['customer_name'] : '';
        $supplierName = is_string($data['supplier_name'] ?? null) ? $data['supplier_name'] : '';
        $customerAddress = Address::fromMixedWithParty($data['customer_address'] ?? null, $customerName);
        $billingCountry = self::normalizeCountry($data['billing_country'] ?? $data['country'] ?? null);

        // Legacy backfill: root 'billing_country'/'country' keys flow into customerAddress->country when missing.
        // customerAddress->country is the canonical DTO source of truth for buyer country.
        if ($customerAddress !== null && $customerAddress->country === null && $billingCountry !== null) {
            $customerAddress->country = $billingCountry;
        }

        $supplierAddress = Address::fromMixedWithParty($data['supplier_address'] ?? null, $supplierName);
        $supplierCountryFromConfig = self::normalizeCountry(config('e-billing.supplier.country_code'));
        if ($supplierAddress !== null && $supplierAddress->country === null && $supplierCountryFromConfig !== null) {
            $supplierAddress->country = $supplierCountryFromConfig;
        }

        $lines = [];
        if (isset($data['lines']) && is_array($data['lines'])) {
            foreach ($data['lines'] as $row) {
                if (is_array($row)) {
                    $lines[] = InvoiceLine::fromArray($row);
                }
            }
        }

        $notes = [];
        if (isset($data['notes']) && is_array($data['notes'])) {
            foreach ($data['notes'] as $n) {
                if (is_string($n)) {
                    $notes[] = $n;
                }
            }
        }

        $supplierBanks = [];
        if (isset($data['supplier_bank_accounts']) && is_array($data['supplier_bank_accounts'])) {
            foreach ($data['supplier_bank_accounts'] as $row) {
                if (is_array($row)) {
                    $supplierBanks[] = $row;
                }
            }
        }

        $invoice = new self(
            invoiceNumber: is_string($data['invoice_number'] ?? null) ? $data['invoice_number'] : '',
            invoiceDate: is_string($data['invoice_date'] ?? null) ? $data['invoice_date'] : '',
            documentType: is_string($data['document_type'] ?? null) && $data['document_type'] !== '' ? $data['document_type'] : 'Rechnung',
            dueDate: isset($data['due_date']) && is_string($data['due_date']) ? $data['due_date'] : null,
            customerNumber: is_string($data['customer_number'] ?? null) ? $data['customer_number'] : '',
            customerName: $customerName,
            customerAddress: $customerAddress,
            customerVatId: isset($data['customer_vat_id']) && is_string($data['customer_vat_id']) ? $data['customer_vat_id'] : null,
            customerReference: isset($data['customer_reference']) && is_string($data['customer_reference']) ? $data['customer_reference'] : null,
            orderNumber: isset($data['order_number']) && is_string($data['order_number']) ? $data['order_number'] : null,
            orderDate: isset($data['order_date']) && is_string($data['order_date']) ? $data['order_date'] : null,
            deliveryAddress: Address::fromMixed($data['delivery_address'] ?? null),
            supplierName: $supplierName,
            supplierVatId: isset($data['supplier_vat_id']) && is_string($data['supplier_vat_id']) ? $data['supplier_vat_id'] : null,
            supplierTaxNumber: isset($data['supplier_tax_number']) && is_string($data['supplier_tax_number']) ? $data['supplier_tax_number'] : null,
            supplierAddress: $supplierAddress,
            supplierNumber: isset($data['supplier_number']) && is_string($data['supplier_number']) ? $data['supplier_number'] : null,
            supplierPhone: isset($data['supplier_phone']) && is_string($data['supplier_phone']) ? $data['supplier_phone'] : null,
            supplierEmail: isset($data['supplier_email']) && is_string($data['supplier_email']) ? $data['supplier_email'] : null,
            supplierBankAccounts: $supplierBanks,
            agent: isset($data['agent']) && is_string($data['agent']) ? $data['agent'] : null,
            paymentTerms: isset($data['payment_terms']) && is_string($data['payment_terms']) ? $data['payment_terms'] : null,
            pricingBasis: isset($data['pricing_basis']) && is_string($data['pricing_basis']) ? $data['pricing_basis'] : null,
            netTotal: (float) ($data['net_total'] ?? 0),
            vatRate: (float) ($data['vat_rate'] ?? 19.0),
            vatAmount: (float) ($data['vat_amount'] ?? 0),
            grossTotal: (float) ($data['gross_total'] ?? 0),
            discountPercent: isset($data['discount_percent']) && is_numeric($data['discount_percent']) ? (float) $data['discount_percent'] : null,
            discountAmount: isset($data['discount_amount']) && is_numeric($data['discount_amount']) ? (float) $data['discount_amount'] : null,
            shippingCost: isset($data['shipping_cost']) && is_numeric($data['shipping_cost']) ? (float) $data['shipping_cost'] : null,
            minimumQuantitySurcharge: isset($data['minimum_quantity_surcharge']) && is_numeric($data['minimum_quantity_surcharge']) ? (float) $data['minimum_quantity_surcharge'] : null,
            freightFlatRate: isset($data['freight_flat_rate']) && is_numeric($data['freight_flat_rate']) ? (float) $data['freight_flat_rate'] : null,
            packagingCost: isset($data['packaging_cost']) && is_numeric($data['packaging_cost']) ? (float) $data['packaging_cost'] : null,
            shippingMethod: isset($data['shipping_method']) && is_string($data['shipping_method']) ? $data['shipping_method'] : null,
            lines: $lines,
            notes: $notes,
            currency: is_string($data['currency'] ?? null) && $data['currency'] !== '' ? $data['currency'] : 'EUR',
        );

        $invoice->applyDefaultCustomerCountry();

        return $invoice;
    }

    /**
     * TRANSITIONAL: fill buyer/delivery country from config when still empty after parse or legacy backfill.
     * Replaced by master-data Company/Address lookup; remove with {@see config('e-billing.default_customer_country')}.
     */
    public function applySupplierSnapshotFromConfig(): void
    {
        $supplier = config('e-billing.supplier');
        if (! is_array($supplier)) {
            $this->applyDefaultCustomerCountry();

            return;
        }

        $this->supplierName = is_string($supplier['name'] ?? null) ? $supplier['name'] : '';
        $this->supplierVatId = is_string($supplier['vat_id'] ?? null) ? $supplier['vat_id'] : null;
        $this->supplierTaxNumber = is_string($supplier['tax_number'] ?? null) ? $supplier['tax_number'] : null;
        $this->supplierAddress = Address::fromMixedWithParty($supplier['address'] ?? null, $this->supplierName);
        if ($this->supplierAddress !== null
            && ($this->supplierAddress->country === null || trim((string) $this->supplierAddress->country) === '')
            && isset($supplier['country_code']) && is_string($supplier['country_code']) && trim($supplier['country_code']) !== '') {
            $this->supplierAddress->country = strtoupper(trim($supplier['country_code']));
        }
        $this->supplierPhone = is_string($supplier['phone'] ?? null) ? $supplier['phone'] : null;
        $this->supplierEmail = is_string($supplier['email'] ?? null) ? $supplier['email'] : null;
        $this->supplierBankAccounts = is_array($supplier['bank_accounts'] ?? null) ? $supplier['bank_accounts'] : [];
        $this->applyDefaultCustomerCountry();
    }

    public function applyDefaultCustomerCountry(): void
    {
        $default = self::normalizeCountry(config('e-billing.default_customer_country'));
        if ($default === null) {
            return;
        }

        if ($this->customerAddress !== null && self::isCountryEmpty($this->customerAddress->country)) {
            $this->customerAddress->country = $default;
        }

        if ($this->deliveryAddress !== null && self::isCountryEmpty($this->deliveryAddress->country)) {
            $this->deliveryAddress->country = $default;
        }
    }

    public function toArray(): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'document_type' => $this->documentType,
            'due_date' => $this->dueDate,

            'customer_number' => $this->customerNumber,
            'customer_name' => $this->customerName,
            'customer_address' => $this->customerAddress?->toArray(),
            'billing_country' => $this->customerAddress?->country,
            'customer_vat_id' => $this->customerVatId,
            'customer_reference' => $this->customerReference,

            'order_number' => $this->orderNumber,
            'order_date' => $this->orderDate,
            'delivery_address' => $this->deliveryAddress?->toArray(),

            'supplier_name' => $this->supplierName,
            'supplier_vat_id' => $this->supplierVatId,
            'supplier_tax_number' => $this->supplierTaxNumber,
            'supplier_address' => $this->supplierAddress?->toArray(),
            'supplier_number' => $this->supplierNumber,
            'supplier_phone' => $this->supplierPhone,
            'supplier_email' => $this->supplierEmail,
            'supplier_bank_accounts' => $this->supplierBankAccounts,

            'agent' => $this->agent,
            'payment_terms' => $this->paymentTerms,
            'pricing_basis' => $this->pricingBasis,

            'net_total' => $this->netTotal,
            'vat_rate' => $this->vatRate,
            'vat_amount' => $this->vatAmount,
            'gross_total' => $this->grossTotal,
            'discount_percent' => $this->discountPercent,
            'discount_amount' => $this->discountAmount,
            'shipping_cost' => $this->shippingCost,
            'minimum_quantity_surcharge' => $this->minimumQuantitySurcharge,
            'freight_flat_rate' => $this->freightFlatRate,
            'packaging_cost' => $this->packagingCost,
            'shipping_method' => $this->shippingMethod,
            'currency' => $this->currency,

            'lines' => array_map(fn (InvoiceLine $line) => $line->toArray(), $this->lines),
            'notes' => $this->notes,
        ];
    }

    private static function normalizeCountry(mixed $country): ?string
    {
        if (! is_string($country) || trim($country) === '') {
            return null;
        }

        return strtoupper(trim($country));
    }

    private static function isCountryEmpty(?string $country): bool
    {
        return $country === null || trim($country) === '';
    }
}
