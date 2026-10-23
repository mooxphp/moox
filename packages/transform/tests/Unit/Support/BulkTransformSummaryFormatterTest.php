<?php

declare(strict_types=1);

use Moox\Transform\Support\Execution\BulkTransformSummaryFormatter;

test('it formats success messages with created and updated counts', function (): void {
    $message = BulkTransformSummaryFormatter::formatMessage([
        'total' => 123567,
        'processed' => 120000,
        'updated' => 3567,
        'skipped' => 0,
        'failed' => 0,
        'failures' => [],
    ]);

    expect($message)
        ->toContain('123567 projections')
        ->toContain('120000 created')
        ->toContain('3567 updated');
});

test('it formats failure samples with source labels', function (): void {
    $message = BulkTransformSummaryFormatter::formatMessage([
        'total' => 10,
        'processed' => 8,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 2,
        'failures' => [
            [
                'source_label' => 'sku=ABC-1',
                'status' => 'failed_validation',
                'error_message' => 'Validation failed.',
            ],
        ],
    ]);

    expect($message)
        ->toContain('2 failed')
        ->toContain('sku ABC-1')
        ->not->toContain('failed_validation');
});

test('it keeps a bulk run processed when only some rows are invalid', function (): void {
    $completion = BulkTransformSummaryFormatter::completion([
        'processed' => 28983,
        'updated' => 1,
        'skipped' => 0,
        'failed' => 23,
    ]);

    expect($completion)->toBe([
        'status' => 'processed',
        'validation_status' => 'valid',
        'degraded' => true,
    ]);
});

test('it fails a bulk run when every row failed', function (): void {
    $completion = BulkTransformSummaryFormatter::completion([
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 23,
    ]);

    expect($completion['status'])->toBe('failed')
        ->and($completion['degraded'])->toBeTrue();
});

test('it can keep the summary line free of failure samples', function (): void {
    $message = BulkTransformSummaryFormatter::formatMessage([
        'total' => 10,
        'processed' => 8,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 2,
        'failures' => [
            [
                'source_label' => 'external_reference=ABC',
                'status' => 'failed_validation',
                'error_message' => 'Validation failed.',
            ],
        ],
    ], withFailures: false);

    expect($message)
        ->toBe('Bulk transform completed with failures: 10 projections (8 created, 2 failed).')
        ->and($message)->not->toContain('Failure samples');
});

test('it resolves projection source labels from destination match paths', function (): void {
    $label = BulkTransformSummaryFormatter::projectionSourceLabel(
        ['artikel' => ['Artikelnummer' => '4711']],
        ['sku' => 'artikel.Artikelnummer'],
    );

    expect($label)->toBe('sku=4711');
});

test('it labels a projection with the source key before a static match field', function (): void {
    $label = BulkTransformSummaryFormatter::projectionSourceLabel(
        [
            'legacy' => ['ID_Firma' => '81CD40A3-5EE6-457E-9083-2E9692CA4CA0', 'Mail' => "\t"],
            'meta' => ['owner_type' => 'Moox\\Contact\\Models\\Contact'],
        ],
        ['owner_type' => 'meta.owner_type'],
        [[
            'alias' => 'legacy',
            'source_type' => 'db_table',
            'key_column' => 'ID_Firma',
        ]],
    );

    expect($label)->toBe('ID_Firma=81CD40A3-5EE6-457E-9083-2E9692CA4CA0');
});

test('it explains a stored validation failure in plain language', function (): void {
    $message = BulkTransformSummaryFormatter::formatMessage([
        'total' => 2,
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'failed' => 2,
        'failures' => [
            [
                'source_label' => 'owner_type=Moox\\Contact\\Models\\Contact',
                'status' => 'failed_validation',
                'error_message' => 'Validation failed. value: validation.required',
            ],
            [
                'source_label' => 'ID_Firma=81CD40A3-5EE6-457E-9083-2E9692CA4CA0',
                'status' => 'failed_validation',
                'error_message' => 'Validation failed. value: validation.required',
            ],
        ],
    ]);

    expect($message)->toContain('Fehler:')
        ->and($message)->toContain('Feld „value“ ist leer')
        ->and($message)->toContain('ID_Firma 81CD40A3-5EE6-457E-9083-2E9692CA4CA0')
        ->and($message)->not->toContain('failed_validation')
        ->and($message)->not->toContain('Moox\\Contact\\Models\\Contact')
        ->and($message)->not->toContain('validation.required');
});

test('it formats a run duration', function (): void {
    expect(BulkTransformSummaryFormatter::humanDuration(0))->toBe('0s')
        ->and(BulkTransformSummaryFormatter::humanDuration(45))->toBe('45s')
        ->and(BulkTransformSummaryFormatter::humanDuration(192))->toBe('3m 12s')
        ->and(BulkTransformSummaryFormatter::humanDuration(3723))->toBe('1h 2m 3s');
});
