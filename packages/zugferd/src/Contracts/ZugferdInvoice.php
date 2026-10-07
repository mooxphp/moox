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

    /** Buyer's national tax number; no EN 16931 term, written as buyer FC registration when there is no VAT id. */
    public ?string $customerTaxNumber { get; }

    public string $supplierName { get; }

    public ?ZugferdAddress $supplierAddress { get; }

    public ?string $supplierPhone { get; }

    public ?string $supplierEmail { get; }

    public ?string $agent { get; }

    public ?string $supplierVatId { get; }

    public ?string $supplierTaxNumber { get; }

    /** BT-29 seller identifier; null/empty → do not emit. */
    public ?string $supplierNumber { get; }

    public ?string $paymentTerms { get; }

    public ?string $deliveryDate { get; }

    public ?string $purchaseOrderReference { get; }

    public ?string $despatchAdviceReference { get; }

    /** Unstructured only (BT-22); never OrderReference IssueDate. */
    public ?string $purchaseOrderDate { get; }

    public ?string $shipToName { get; }

    public ?ZugferdAddress $shipToAddress { get; }

    public ?string $paymentMeansCode { get; }

    public string $vatCategoryCode { get; }

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

    /** @var list<string> */
    public array $documentNotes { get; }

    /**
     * BG-3 preceding invoice references: BT-25 number, BT-26 issue date (Y-m-d).
     *
     * @var list<array{number: string, date: ?string}>
     */
    public array $precedingInvoices { get; }
}
