<?php

declare(strict_types=1);

use Moox\Zugferd\ZugferdConverter;

require_once __DIR__.'/../Support/MinimalZugferdInvoice.php';

it('emits the payment terms as given, with the due date kept structured', function (): void {
    $invoice = minimalZugferdInvoice();
    $invoice->paymentTerms = "Net 30 days\nPayable by 14.02.2026";
    $invoice->dueDate = '2026-02-14';

    $xml = app(ZugferdConverter::class)->convert($invoice, 'EN16931');

    expect($xml)
        ->toMatch(
            "#<ram:Description>Net 30 days\nPayable by 14\.02\.2026</ram:Description>\s*"
            .'<ram:DueDateDateTime>\s*<udt:DateTimeString format="102">20260214</udt:DateTimeString>#',
        );
});

it('emits only the due date when no payment terms are given', function (): void {
    $invoice = minimalZugferdInvoice();
    $invoice->dueDate = '2026-02-14';

    $xml = app(ZugferdConverter::class)->convert($invoice, 'EN16931');

    expect($xml)
        ->toMatch(
            '#<ram:SpecifiedTradePaymentTerms>\s*<ram:DueDateDateTime>\s*'
            .'<udt:DateTimeString format="102">20260214</udt:DateTimeString>#',
        )
        ->not->toContain('<ram:Description>');
});

it('emits the payment terms without a due date', function (): void {
    $invoice = minimalZugferdInvoice();
    $invoice->paymentTerms = 'Net 30 days';

    $xml = app(ZugferdConverter::class)->convert($invoice, 'EN16931');

    expect($xml)
        ->toContain('<ram:Description>Net 30 days</ram:Description>')
        ->not->toContain('<ram:DueDateDateTime>');
});
