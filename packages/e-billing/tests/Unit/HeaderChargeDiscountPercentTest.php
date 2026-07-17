<?php

declare(strict_types=1);

use Moox\EBilling\Data\Invoice;
use Moox\EBilling\Support\BillDataAllowanceChargeMapper;
use Moox\EBilling\Support\HeaderChargeResolver;
use Moox\EBilling\Tests\TestCase;
use Moox\Invoice\Models\InvoiceAllowanceCharge;

uses(TestCase::class);

test('discount percent is populated on allowance charge and resolved from percentage column', function (): void {
    $charges = BillDataAllowanceChargeMapper::fromHeaderScalars(
        shippingCost: null,
        packagingCost: null,
        minimumQuantitySurcharge: null,
        freightFlatRate: null,
        discountAmount: 7.71,
        discountPercent: 3.0,
    );

    expect($charges)->toHaveCount(1)
        ->and($charges[0]->percentage)->toBe(3.0)
        ->and($charges[0]->reasonCode)->toBe('95');

    $modelCharge = new InvoiceAllowanceCharge([
        'is_charge' => false,
        'amount' => '7.71',
        'reason_code' => '95',
        'reason_text' => '3 % vom Warenwert',
        'percentage' => '3',
    ]);

    expect(HeaderChargeResolver::resolveDiscountPercent([$modelCharge]))->toBe(3.0);
});

test('invoice DTO allowance charges preserve discount percent for resolver', function (): void {
    $invoice = new Invoice(
        invoiceNumber: '1',
        invoiceDate: '2026-01-01',
        discountAmount: 7.71,
        discountPercent: 3.0,
    );

    $charge = $invoice->allowanceCharges[0];

    expect($charge->percentage)->toBe(3.0);

    $modelCharge = new InvoiceAllowanceCharge([
        'is_charge' => false,
        'amount' => (string) $charge->amount,
        'reason_code' => $charge->reasonCode,
        'reason_text' => $charge->reasonText,
        'percentage' => (string) $charge->percentage,
    ]);

    expect(HeaderChargeResolver::resolveDiscountPercent([$modelCharge]))->toBe(3.0);
});
