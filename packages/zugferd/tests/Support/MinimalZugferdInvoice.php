<?php

declare(strict_types=1);

use Moox\Zugferd\Contracts\ZugferdAddress;
use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdBankAccount;
use Moox\Zugferd\Contracts\ZugferdInvoice;
use Moox\Zugferd\Contracts\ZugferdInvoiceLine;
use Moox\Zugferd\Contracts\ZugferdItemAttribute;
use Moox\Zugferd\Contracts\ZugferdItemClassification;
use Moox\Zugferd\Data\PostalAddress;

/**
 * One-line EN 16931 invoice (100.00 net, 19 % VAT) for converter tests.
 *
 * @param  list<ZugferdAllowanceCharge>  $lineAllowanceCharges
 * @param  list<ZugferdAllowanceCharge>  $documentAllowanceCharges
 */
function minimalZugferdInvoice(
    float $lineTotal = 100.0,
    array $lineAllowanceCharges = [],
    array $documentAllowanceCharges = [],
    float $netTotal = 100.0,
): ZugferdInvoice {
    $address = new PostalAddress(
        street: 'Main 1',
        zip: '10115',
        city: 'Berlin',
        country: 'DE',
    );

    $line = new class($lineTotal, $lineAllowanceCharges) implements ZugferdInvoiceLine
    {
        public int $position = 1;

        public string $description = 'Test item';

        public ?string $descriptionDetail = null;

        public ?string $articleNumber = null;

        public float $unitPrice;

        public float $quantity = 1.0;

        public string $unit = 'Stück';

        public string $unitCode = 'H87';

        public ?string $deliveryDate = null;

        public ?string $shipToName = null;

        public ?ZugferdAddress $shipToAddress = null;

        public ?string $purchaseOrderLineReference = null;

        public ?string $orderDocumentReference = null;

        public ?string $deliveryNoteNumber = null;

        public array $partialDeliveries = [];

        public ?string $purchaseOrderDate = null;

        /** @var list<ZugferdItemAttribute> */
        public array $itemAttributes = [];

        /** @var list<ZugferdItemClassification> */
        public array $itemClassifications = [];

        /**
         * @param  list<ZugferdAllowanceCharge>  $allowanceCharges
         */
        public function __construct(
            public float $lineTotal,
            public array $allowanceCharges,
        ) {
            $this->unitPrice = $lineTotal;
        }
    };

    $bank = new class implements ZugferdBankAccount
    {
        public string $iban = 'DE89370400440532013000';

        public ?string $bic = 'COBADEFFXXX';

        public ?string $bankName = 'Test Bank';

        public ?string $accountHolder = null;
    };

    return new class($address, $address, $line, $bank, $documentAllowanceCharges, $netTotal) implements ZugferdInvoice
    {
        public string $invoiceNumber = '2026001';

        public string $invoiceDate = '2026-01-15';

        public string $documentType = 'Rechnung';

        public string $documentTypeCode = '380';

        public ?string $dueDate = null;

        public string $currency = 'EUR';

        public string $customerNumber = 'C-1';

        public ?string $customerReference = null;

        public string $customerName = 'Buyer GmbH';

        public ?string $customerVatId = null;

        public string $supplierName = 'Seller GmbH';

        public ?string $supplierPhone = null;

        public ?string $supplierEmail = 'billing@seller.test';

        public ?string $agent = null;

        public ?string $supplierVatId = 'DE123456789';

        public ?string $supplierTaxNumber = null;

        public ?string $supplierNumber = null;

        public ?string $paymentTerms = null;

        public ?string $deliveryDate = null;

        public ?string $purchaseOrderReference = null;

        public ?string $despatchAdviceReference = null;

        public ?string $purchaseOrderDate = null;

        public ?string $shipToName = null;

        public ?ZugferdAddress $shipToAddress = null;

        public ?string $paymentMeansCode = '58';

        public string $vatCategoryCode = 'S';

        public float $vatRate = 19.0;

        public float $vatAmount;

        public float $grossTotal;

        /** @var list<ZugferdInvoiceLine> */
        public array $lines;

        /** @var list<ZugferdBankAccount> */
        public array $bankAccounts;

        /** @var list<string> */
        public array $documentNotes = [];

        /** @var list<array{number: string, date: ?string}> */
        public array $precedingInvoices = [];

        /**
         * @param  list<ZugferdAllowanceCharge>  $allowanceCharges
         */
        public function __construct(
            public ?ZugferdAddress $customerAddress,
            public ?ZugferdAddress $supplierAddress,
            ZugferdInvoiceLine $line,
            ZugferdBankAccount $bank,
            public array $allowanceCharges,
            public float $netTotal,
        ) {
            $this->lines = [$line];
            $this->bankAccounts = [$bank];
            $this->vatAmount = round($netTotal * 0.19, 2);
            $this->grossTotal = round($netTotal + $this->vatAmount, 2);
        }
    };
}
