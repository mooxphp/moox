<?php

declare(strict_types=1);

use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\Exceptions\UnknownFormatException;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Formats\Strategies\ZugferdGeneratorStrategy;
use Moox\EBilling\Tests\ContainerTestCase;

uses(ContainerTestCase::class);

test('registry exposes all three format definitions', function (): void {
    $registry = app(FormatRegistry::class);

    expect($registry->has('xrechnung'))->toBeTrue()
        ->and($registry->has('zugferd'))->toBeTrue()
        ->and($registry->has('factur-x'))->toBeTrue();

    $xrechnung = $registry->get('xrechnung');
    expect($xrechnung->id)->toBe('xrechnung')
        ->and($xrechnung->label)->toBe('XRechnung')
        ->and($xrechnung->artifactKind)->toBe(ArtifactKind::Xml)
        ->and($xrechnung->profile)->toBe('XRECHNUNG')
        ->and($xrechnung->strategy)->toBeInstanceOf(ZugferdGeneratorStrategy::class);

    $zugferd = $registry->get('zugferd');
    expect($zugferd->id)->toBe('zugferd')
        ->and($zugferd->label)->toBe('ZUGFeRD')
        ->and($zugferd->artifactKind)->toBe(ArtifactKind::Pdf)
        ->and($zugferd->profile)->toBe((string) config('zugferd.profile', 'EN16931'))
        ->and($zugferd->strategy)->toBeInstanceOf(ZugferdGeneratorStrategy::class);

    $facturX = $registry->get('factur-x');
    expect($facturX->id)->toBe('factur-x')
        ->and($facturX->label)->toBe('Factur-X')
        ->and($facturX->artifactKind)->toBe(ArtifactKind::Pdf)
        ->and($facturX->strategy)->toBeInstanceOf(ZugferdGeneratorStrategy::class);
});

test('registry labels returns all three customer-facing labels', function (): void {
    $labels = app(FormatRegistry::class)->labels();

    expect($labels)->toBe([
        'xrechnung' => 'XRechnung',
        'zugferd' => 'ZUGFeRD',
        'factur-x' => 'Factur-X',
    ]);
});

test('registry get throws on unknown format', function (): void {
    $registry = app(FormatRegistry::class);

    expect(fn () => $registry->get('unknown-format'))
        ->toThrow(UnknownFormatException::class);
});
