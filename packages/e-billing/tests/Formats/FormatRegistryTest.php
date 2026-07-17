<?php

declare(strict_types=1);

use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\Exceptions\UnknownFormatException;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Formats\Strategies\ZugferdGeneratorStrategy;
use Moox\EBilling\Tests\ContainerTestCase;

uses(ContainerTestCase::class);

test('registry has and get return the zugferd format definition', function (): void {
    $registry = app(FormatRegistry::class);

    expect($registry->has('zugferd'))->toBeTrue()
        ->and($registry->has('xrechnung'))->toBeFalse();

    $definition = $registry->get('zugferd');

    expect($definition->id)->toBe('zugferd')
        ->and($definition->label)->toBe('ZUGFeRD')
        ->and($definition->artifactKind)->toBe(ArtifactKind::Pdf)
        ->and($definition->profile)->toBe((string) config('zugferd.profile', 'EN16931'))
        ->and($definition->strategy)->toBeInstanceOf(ZugferdGeneratorStrategy::class);
});

test('registry get throws on unknown format', function (): void {
    $registry = app(FormatRegistry::class);

    expect(fn () => $registry->get('unknown-format'))
        ->toThrow(UnknownFormatException::class);
});
