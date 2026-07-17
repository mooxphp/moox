<?php

declare(strict_types=1);

use Moox\EBilling\Support\DocumentTypeCodeResolver;
use Moox\EBilling\Tests\Support\InvoiceFixtures;
use Moox\EBilling\Tests\TestCase;
use Moox\Zugferd\ZugferdConverter;

uses(TestCase::class);

test('Gutschrift end-to-end emits UNTDID 381 in XML', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $resolver = app(DocumentTypeCodeResolver::class);
    $invoice = InvoiceFixtures::minimal(
        documentType: 'Gutschrift',
        documentTypeCode: $resolver->resolveLabel('Gutschrift'),
    );

    $xml = app(ZugferdConverter::class)->convert($invoice);

    expect($xml)->toMatch('/(?:TypeCode|DocumentTypeCode)[^>]*>381</');
});

test('invoice end-to-end emits UNTDID 380 in XML', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $resolver = app(DocumentTypeCodeResolver::class);
    $invoice = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: $resolver->resolveLabel('Rechnung'),
    );

    $xml = app(ZugferdConverter::class)->convert($invoice);

    expect($xml)->toMatch('/(?:TypeCode|DocumentTypeCode)[^>]*>380</');
});
