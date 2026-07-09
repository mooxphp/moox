<?php

declare(strict_types=1);

namespace Moox\Zugferd\Contracts;

interface ZugferdInvoice
{
    public string $invoiceNumber { get; }

    public string $invoiceDate { get; }

    public string $documentType { get; }

    public string $documentTypeCode { get; }

    public ?string $dueDate { get; }

    public string $currency { get; }

    public string $customerNumber { get; }

    public ?string $customerReference { get; }

    public string $customerName { get; }

    public ?ZugferdAddress $customerAddress { get; }

    public ?string $customerVatId { get; }

    public string $supplierName { get; }

    public ?ZugferdAddress $supplierAddress { get; }

    public ?string $supplierPhone { get; }

    public ?string $supplierEmail { get; }

    public ?string $agent { get; }

    public ?string $supplierVatId { get; }

    public ?string $supplierTaxNumber { get; }

    public ?string $paymentTerms { get; }

    public ?string $paymentMeansCode { get; }

    public float $vatRate { get; }

    public float $netTotal { get; }

    public float $vatAmount { get; }

    public float $grossTotal { get; }

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges { get; }

    /** @var list<ZugferdInvoiceLine> */
    public array $lines { get; }

    /** @var list<ZugferdBankAccount> */
    public array $bankAccounts { get; }
}
