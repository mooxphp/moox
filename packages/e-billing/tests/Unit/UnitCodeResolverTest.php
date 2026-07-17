<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Moox\EBilling\Exceptions\CodelistNotImportedException;
use Moox\EBilling\Support\UnitCodeResolver;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('empty unit codelist throws CodelistNotImportedException', function (): void {
    expect(fn () => app(UnitCodeResolver::class)->resolveLabel('Stück'))
        ->toThrow(CodelistNotImportedException::class);
});

test('seeded codelist resolves Stück to C62 and Meter to MTR', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $resolver = app(UnitCodeResolver::class);

    expect($resolver->resolveLabel('Stück'))->toBe('C62')
        ->and($resolver->resolveLabel('Meter'))->toBe('MTR');
});

test('resolving many unit labels issues only one codelist query', function (): void {
    $this->seedDocumentTypeAndUnitCodelists();

    $resolver = app(UnitCodeResolver::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    foreach (['Stück', 'Meter', 'Stück Loser Alu-Flansch', 'Meter Geschw. Rohr'] as $label) {
        $resolver->resolveLabel($label);
    }

    $unitQueries = array_values(array_filter(
        DB::getQueryLog(),
        fn (array $query): bool => str_contains($query['query'], 'static_units'),
    ));

    expect($unitQueries)->toHaveCount(1);
});
