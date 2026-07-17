<?php

declare(strict_types=1);

use Moox\EBilling\Exceptions\CodelistNotImportedException;
use Moox\EBilling\Exceptions\UnresolvedCodelistLabelException;
use Moox\EBilling\Support\DocumentTypeCodeResolver;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('empty document type codelist throws CodelistNotImportedException', function (): void {
    expect(fn () => app(DocumentTypeCodeResolver::class)->resolveLabel('Rechnung'))
        ->toThrow(CodelistNotImportedException::class);
});

test('seeded codelist resolves Gutschrift to 381 and Rechnung to 380', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $resolver = app(DocumentTypeCodeResolver::class);

    expect($resolver->resolveLabel('Gutschrift'))->toBe('381')
        ->and($resolver->resolveLabel('Rechnung'))->toBe('380')
        ->and($resolver->labelFor('381'))->toBe('Gutschrift')
        ->and($resolver->labelFor('380'))->toBe('Handelsrechnung');
});

test('unresolved document type label throws', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    expect(fn () => app(DocumentTypeCodeResolver::class)->resolveLabel('NotARealDocumentType'))
        ->toThrow(UnresolvedCodelistLabelException::class);
});

test('resolveFromCodeOrLabel returns non-empty raw code as-is', function (): void {
    $resolver = app(DocumentTypeCodeResolver::class);

    expect($resolver->resolveFromCodeOrLabel('999', ''))->toBe('999')
        ->and($resolver->resolveFromCodeOrLabel('380', 'Rechnung'))->toBe('380');
});

test('resolveFromCodeOrLabel falls through to label resolution when code is empty', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $resolver = app(DocumentTypeCodeResolver::class);

    expect($resolver->resolveFromCodeOrLabel('', 'Gutschrift'))->toBe('381')
        ->and($resolver->resolveFromCodeOrLabel('', 'Rechnung'))->toBe('380');
});
