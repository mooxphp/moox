<?php

declare(strict_types=1);

namespace Moox\EBilling\Tests\Support;

use Moox\EBilling\Data\Address;
use Moox\EBilling\Data\Invoice;
use Moox\EBilling\Data\InvoiceLine;

final class InvoiceFixtures
{
    public static function minimal(
        string $documentType,
        string $documentTypeCode,
        string $unitCode = 'C62',
    ): Invoice {
        return new Invoice(
            invoiceNumber: '2026001',
            invoiceDate: '2026-01-15',
            documentType: $documentType,
            documentTypeCode: $documentTypeCode,
            customerName: 'Buyer GmbH',
            customerAddress: new Address(
                company: 'Buyer GmbH',
                street: 'Main 1',
                zip: '10115',
                city: 'Berlin',
                country: 'DE',
            ),
            supplierName: 'Seller GmbH',
            supplierAddress: new Address(
                company: 'Seller GmbH',
                street: 'Industriestr 2',
                zip: '80331',
                city: 'München',
                country: 'DE',
            ),
            supplierEmail: 'billing@seller.test',
            supplierBankAccounts: [
                [
                    'iban' => 'DE89370400440532013000',
                    'bic' => 'COBADEFFXXX',
                    'bank_name' => 'Test Bank',
                ],
            ],
            netTotal: 100.0,
            vatRate: 19.0,
            vatAmount: 19.0,
            grossTotal: 119.0,
            lines: [
                new InvoiceLine(
                    position: 1,
                    unit: 'Stück',
                    unitCode: $unitCode,
                    quantity: 1,
                    description: 'Test item',
                    unitPrice: 100.0,
                    lineTotal: 100.0,
                ),
            ],
        );
    }
}
