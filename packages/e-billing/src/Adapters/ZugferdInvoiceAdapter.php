<?php

declare(strict_types=1);

namespace Moox\EBilling\Adapters;

use Moox\EBilling\Support\DocumentTypeCodeResolver;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdAddress;
use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdBankAccount;
use Moox\Zugferd\Contracts\ZugferdInvoice;
use Moox\Zugferd\Contracts\ZugferdInvoiceLine;
use Moox\Zugferd\Data\AllowanceCharge;

final class ZugferdInvoiceAdapter implements ZugferdInvoice
{
    public function __construct(
        private Invoice $model,
        private ?DocumentTypeCodeResolver $documentTypeCodeResolver = null,
    ) {
        $this->documentTypeCodeResolver ??= app(DocumentTypeCodeResolver::class);
    }

    public string $invoiceNumber {
        get => (string) $this->model->invoice_number;
    }

    public string $invoiceDate {
        get => (string) $this->model->invoice_date;
    }

    public string $documentType {
        get => $this->documentTypeCodeResolver->labelFor((string) $this->model->document_type);
    }

    public string $documentTypeCode {
        get => (string) $this->model->document_type;
    }

    public ?string $dueDate {
        get => $this->model->due_date !== null ? (string) $this->model->due_date : null;
    }

    public string $currency {
        get => (string) ($this->model->currency ?? 'EUR');
    }

    public string $customerNumber {
        get => '';
    }

    public ?string $customerReference {
        get => $this->model->customer_reference;
    }

    public string $customerName {
        get => $this->model->buyer?->name ?? '';
    }

    public ?ZugferdAddress $customerAddress {
        get => $this->model->buyer !== null
            ? new ZugferdAddressAdapter($this->model->buyer->address)
            : null;
    }

    public ?string $customerVatId {
        get => $this->model->buyer?->vat_id;
    }

    public string $supplierName {
        get => $this->model->seller?->name ?? '';
    }

    public ?ZugferdAddress $supplierAddress {
        get => $this->model->seller !== null
            ? new ZugferdAddressAdapter($this->model->seller->address)
            : null;
    }

    public ?string $supplierPhone {
        get => $this->model->seller?->contact?->phone;
    }

    public ?string $supplierEmail {
        get => $this->model->seller?->contact?->email;
    }

    public ?string $agent {
        get {
            $name = $this->model->seller?->contact?->name;

            if ($name === null || trim($name) === '') {
                return null;
            }

            return trim($name);
        }
    }

    public ?string $supplierVatId {
        get => $this->model->seller?->vat_id;
    }

    public ?string $supplierTaxNumber {
        get => $this->model->seller?->tax_number;
    }

    public ?string $paymentTerms {
        get => null;
    }

    public ?string $paymentMeansCode {
        get => $this->model->payment_means?->payment_means_code;
    }

    public float $vatRate {
        get => (float) $this->model->vat_rate;
    }

    public float $netTotal {
        get => (float) $this->model->net_total;
    }

    public float $vatAmount {
        get => (float) $this->model->vat_amount;
    }

    public float $grossTotal {
        get => (float) $this->model->gross_total;
    }

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges {
        get {
            $this->model->loadMissing('allowanceCharges');

            return $this->model->allowanceCharges
                ->map(fn (InvoiceAllowanceCharge $charge): AllowanceCharge => self::mapAllowanceCharge($charge))
                ->values()
                ->all();
        }
    }

    /** @var list<ZugferdInvoiceLine> */
    public array $lines {
        get {
            $this->model->loadMissing(['lines.allowanceCharges']);

            return $this->model->lines
                ->map(fn ($line): ZugferdInvoiceLineAdapter => new ZugferdInvoiceLineAdapter($line))
                ->values()
                ->all();
        }
    }

    /** @var list<ZugferdBankAccount> */
    public array $bankAccounts {
        get {
            $accounts = $this->model->payment_means?->bank_accounts ?? [];

            return array_map(
                fn ($account): ZugferdBankAccountAdapter => new ZugferdBankAccountAdapter($account),
                $accounts,
            );
        }
    }

    private static function mapAllowanceCharge(InvoiceAllowanceCharge $charge): AllowanceCharge
    {
        return new AllowanceCharge(
            isCharge: (bool) $charge->is_charge,
            amount: (float) $charge->amount,
            reasonCode: $charge->reason_code,
            reasonText: $charge->reason_text,
            basisAmount: $charge->base_amount !== null ? (float) $charge->base_amount : null,
            percentage: $charge->percentage !== null ? (float) $charge->percentage : null,
        );
    }
}
