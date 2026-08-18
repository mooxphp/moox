<?php

declare(strict_types=1);

namespace Moox\EBilling\Adapters;

use Moox\EBilling\Data\Invoice;
use Moox\EBilling\Support\DeliveryShipTo;
use Moox\Zugferd\Contracts\ZugferdAddress;
use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdBankAccount;
use Moox\Zugferd\Contracts\ZugferdInvoice;
use Moox\Zugferd\Contracts\ZugferdInvoiceLine;

final class ZugferdInvoiceDtoAdapter implements ZugferdInvoice
{
    public function __construct(
        private Invoice $invoice,
    ) {
    }

    public string $invoiceNumber {
        get => $this->invoice->invoiceNumber;
    }

    public string $invoiceDate {
        get => $this->invoice->invoiceDate;
    }

    public string $documentType {
        get => $this->invoice->documentType;
    }

    public string $documentTypeCode {
        get => $this->invoice->documentTypeCode;
    }

    public ?string $dueDate {
        get => $this->invoice->dueDate;
    }

    public string $currency {
        get => $this->invoice->currency;
    }

    public string $customerNumber {
        get => $this->invoice->customerNumber;
    }

    public ?string $customerReference {
        get => $this->invoice->customerReference;
    }

    public string $customerName {
        get => $this->invoice->customerName;
    }

    public ?ZugferdAddress $customerAddress {
        get => $this->invoice->customerAddress;
    }

    public ?string $customerVatId {
        get => $this->invoice->customerVatId;
    }

    public string $supplierName {
        get => $this->invoice->supplierName;
    }

    public ?ZugferdAddress $supplierAddress {
        get => $this->invoice->supplierAddress;
    }

    public ?string $supplierPhone {
        get => $this->invoice->supplierPhone;
    }

    public ?string $supplierEmail {
        get => $this->invoice->supplierEmail;
    }

    public ?string $agent {
        get => $this->invoice->agent;
    }

    public ?string $supplierVatId {
        get => $this->invoice->supplierVatId;
    }

    public ?string $supplierTaxNumber {
        get => $this->invoice->supplierTaxNumber;
    }

    public ?string $paymentTerms {
        get => $this->invoice->paymentTerms;
    }

    public ?string $deliveryDate {
        get => $this->invoice->deliveryDate;
    }

    public ?string $shipToName {
        get {
            return DeliveryShipTo::name($this->invoice->deliveryAddress);
        }
    }

    public ?ZugferdAddress $shipToAddress {
        get {
            return DeliveryShipTo::address($this->invoice->deliveryAddress);
        }
    }

    public ?string $paymentMeansCode {
        get => $this->invoice->paymentMeansCode;
    }

    public float $vatRate {
        get => $this->invoice->vatRate;
    }

    public float $netTotal {
        get => $this->invoice->netTotal;
    }

    public float $vatAmount {
        get => $this->invoice->vatAmount;
    }

    public float $grossTotal {
        get => $this->invoice->grossTotal;
    }

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges {
        get => $this->invoice->allowanceCharges();
    }

    /** @var list<ZugferdInvoiceLine> */
    public array $lines {
        get => $this->invoice->lines;
    }

    /** @var list<ZugferdBankAccount> */
    public array $bankAccounts {
        get => $this->invoice->bankAccounts();
    }
}
