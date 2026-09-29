<?php

declare(strict_types=1);

namespace Moox\Invoice\Support;

use Moox\Invoice\Support\En16931\Party;
use Moox\Invoice\Support\En16931\PaymentMeans;

readonly class InvoiceDraft
{
    /**
     * @param  list<InvoiceLineDraft>  $lines
     * @param  list<ChargeDraft>  $headerCharges
     * @param  list<string>  $notes
     * @param  list<array{number: string, date: ?string}>  $preceding_invoices  BG-3: BT-25 number, BT-26 date (Y-m-d)
     */
    public function __construct(
        public string $invoice_number,
        public string $invoice_date,
        public string $document_type,
        public ?string $due_date,
        public string $currency,
        public ?string $customer_number,
        public ?string $supplier_number,
        public ?string $customer_reference,
        public ?string $order_number,
        public ?string $order_date,
        public ?string $delivery_date,
        public ?string $payment_terms,
        public ?string $shipping_method,
        public ?string $delivery_terms,
        public float $net_total,
        public float $vat_rate,
        public float $vat_amount,
        public float $gross_total,
        public ?Party $seller,
        public ?Party $buyer,
        public ?Party $delivery,
        public ?PaymentMeans $payment_means,
        public ?string $vat_category,
        public array $lines,
        public array $headerCharges,
        public array $notes = [],
        public array $preceding_invoices = [],
    ) {
    }
}
