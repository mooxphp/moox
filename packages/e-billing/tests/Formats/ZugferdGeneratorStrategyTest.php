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

    $definition = app(FormatRegistry::class)->get('zugferd');
    $expected = app(ZugferdConverter::class)->convert($invoice, $definition->profile);
    $actual = app(ZugferdGeneratorStrategy::class)->generateXml($invoice, $definition->profile);

    expect($actual)->toBe($expected)
        ->and($actual)->not->toBe('');
});

test('zugferd registry strategy produces identical XML bytes to ZugferdConverter', function (): void {
    $invoice = InvoiceFixtures::minimal(
        documentType: 'Gutschrift',
        documentTypeCode: '381',
    );

    $definition = app(FormatRegistry::class)->get('zugferd');
    $expected = app(ZugferdConverter::class)->convert($invoice, $definition->profile);
    $actual = $definition->strategy->generateXml($invoice, $definition->profile);

    expect($actual)->toBe($expected);
});

test('xrechnung strategy produces XML with XRECHNUNG profile', function (): void {
    $invoice = InvoiceFixtures::minimal(
        documentType: 'Rechnung',
        documentTypeCode: '380',
    );

    $definition = app(FormatRegistry::class)->get('xrechnung');
    $xml = $definition->strategy->generateXml($invoice, $definition->profile);

    expect($xml)->not->toBe('')
        ->and($definition->profile)->toBe('XRECHNUNG');
});
