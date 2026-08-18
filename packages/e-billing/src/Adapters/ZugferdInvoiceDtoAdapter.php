<?php

declare(strict_types=1);

namespace Moox\EBilling\Adapters;

use Moox\EBilling\Data\Invoice;
use Moox\Zugferd\Contracts\ZugferdAddress;
use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdBankAccount;
use Moox\Zugferd\Contracts\ZugferdInvoice;
use Moox\Zugferd\Contracts\ZugferdInvoiceLine;

/**
 * Flat {@see ZugferdInvoice} view of parsed bill_data; public field count mirrors the contract.
 */
final class ZugferdInvoiceDtoAdapter implements ZugferdInvoice
{
    public string $invoiceNumber;

    public string $invoiceDate;

    public string $documentType;

    public string $documentTypeCode;

    public ?string $dueDate;

    public string $currency;

    public string $customerNumber;

    public ?string $customerReference;

    public string $customerName;

    public ?ZugferdAddress $customerAddress;

    public ?string $customerVatId;

    public string $supplierName;

    public ?ZugferdAddress $supplierAddress;

    public ?string $supplierPhone;

    public ?string $supplierEmail;

    public ?string $agent;

    public ?string $supplierVatId;

    public ?string $supplierTaxNumber;

    public ?string $paymentTerms;

    public ?string $deliveryDate;

    public ?string $shipToName;

    public ?ZugferdAddress $shipToAddress;

    public ?string $paymentMeansCode;

    public float $vatRate;

    public float $netTotal;

    public float $vatAmount;

    public float $grossTotal;

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges;

    /** @var list<ZugferdInvoiceLine> */
    public array $lines;

    /** @var list<ZugferdBankAccount> */
    public array $bankAccounts;

    public function __construct(Invoice $invoice)
    {
        $this->invoiceNumber = $invoice->invoiceNumber;
        $this->invoiceDate = $invoice->invoiceDate;
        $this->documentType = $invoice->documentType;
        $this->documentTypeCode = $invoice->documentTypeCode;
        $this->dueDate = $invoice->dueDate;
        $this->currency = $invoice->currency;
        $this->customerNumber = $invoice->customerNumber;
        $this->customerReference = $invoice->customerReference;
        $this->customerName = $invoice->customerName;
        $this->customerAddress = $invoice->customerAddress;
        $this->customerVatId = $invoice->customerVatId;
        $this->supplierName = $invoice->supplierName;
        $this->supplierAddress = $invoice->supplierAddress;
        $this->supplierPhone = $invoice->supplierPhone;
        $this->supplierEmail = $invoice->supplierEmail;
        $this->agent = $invoice->agent;
        $this->supplierVatId = $invoice->supplierVatId;
        $this->supplierTaxNumber = $invoice->supplierTaxNumber;
        $this->paymentTerms = $invoice->paymentTerms;
        $this->deliveryDate = $invoice->deliveryDate;
        $deliveryAddress = $invoice->deliveryAddress;
        $shipToName = $deliveryAddress?->company;
        $this->shipToName = $shipToName !== null && trim($shipToName) !== '' ? trim($shipToName) : null;
        $this->shipToAddress = $deliveryAddress;
        $this->paymentMeansCode = $invoice->paymentMeansCode;
        $this->vatRate = $invoice->vatRate;
        $this->netTotal = $invoice->netTotal;
        $this->vatAmount = $invoice->vatAmount;
        $this->grossTotal = $invoice->grossTotal;
        $this->allowanceCharges = $invoice->allowanceCharges();
        $this->lines = $invoice->lines;
        $this->bankAccounts = $invoice->bankAccounts();
    }
}
