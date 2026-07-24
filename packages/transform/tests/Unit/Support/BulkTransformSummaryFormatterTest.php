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
        ->toContain('sku=ABC-1')
        ->toContain('Validation failed.');
});

test('it resolves projection source labels from destination match paths', function (): void {
    $label = BulkTransformSummaryFormatter::projectionSourceLabel(
        ['artikel' => ['Artikelnummer' => '4711']],
        ['sku' => 'artikel.Artikelnummer'],
    );

    expect($label)->toBe('sku=4711');
});
