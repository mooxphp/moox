<?php

declare(strict_types=1);

use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Formats\Strategies\ZugferdGeneratorStrategy;
use Moox\EBilling\Tests\ContainerTestCase;
use Moox\EBilling\Tests\Support\InvoiceFixtures;
use Moox\Zugferd\ZugferdConverter;

uses(ContainerTestCase::class);

test('zugferd strategy produces identical XML bytes to ZugferdConverter', function (): void {
    $invoice = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    );

    $expected = app(ZugferdConverter::class)->convert($invoice);
    $actual = app(ZugferdGeneratorStrategy::class)->generateXml($invoice);

    expect($actual)->toBe($expected)
        ->and($actual)->not->toBe('');
});

test('zugferd registry strategy produces identical XML bytes to ZugferdConverter', function (): void {
    $invoice = InvoiceFixtures::minimal(
        documentType: 'Gutschrift',
        documentTypeCode: '381',
    );

    $expected = app(ZugferdConverter::class)->convert($invoice);
    $actual = app(FormatRegistry::class)->get('zugferd')->strategy->generateXml($invoice);

    expect($actual)->toBe($expected);
});
