<?php

declare(strict_types=1);

use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\Expansion\TransformProjectionExpander;
use Tests\TestCase;

uses(TestCase::class);

it('uses cursor strategy when expand is only an empty filament scaffold', function (): void {
    $definition = new TransformDefinition([
        'execution_mode' => 'bulk',
        'bulk' => [
            'source' => [
                'strategy' => 'cursor',
                'chunk_size' => 500,
            ],
        ],
        'expand' => [
            'nested' => [
                'path' => null,
                'alias' => 'nested',
                'dedupe_by' => null,
            ],
            'prefer' => [],
            'locales' => [
                'only' => null,
                'alias' => 'lang',
                'source' => null,
                'language_key' => null,
                'locale_field' => null,
            ],
            'dedupe_by' => null,
        ],
        'source_references' => [
            [
                'alias' => 'kunde',
                'table' => 'Kundenstamm',
                'connection' => 'comwork',
                'key_column' => 'ID_Kunde',
                'source_type' => 'db_table',
                'row_key' => null,
                'row_key_from' => null,
            ],
        ],
    ]);

    $record = new TransformRecord([
        'source_references' => $definition->source_references,
    ]);

    $expander = app(TransformProjectionExpander::class);
    $method = new ReflectionMethod($expander, 'shouldUseCursorStrategy');

    expect($method->invoke($expander, $record, $definition))->toBeTrue();
});

it('does not use cursor strategy when expand nested path is set', function (): void {
    $definition = new TransformDefinition([
        'execution_mode' => 'bulk',
        'bulk' => [
            'source' => [
                'strategy' => 'cursor',
            ],
        ],
        'expand' => [
            'nested' => [
                'path' => 'items',
                'alias' => 'nested',
            ],
        ],
        'source_references' => [
            [
                'alias' => 'kunde',
                'table' => 'Kundenstamm',
                'connection' => 'comwork',
                'key_column' => 'ID_Kunde',
                'source_type' => 'db_table',
                'row_key' => null,
            ],
        ],
    ]);

    $record = new TransformRecord([
        'source_references' => $definition->source_references,
    ]);

    $expander = app(TransformProjectionExpander::class);
    $method = new ReflectionMethod($expander, 'shouldUseCursorStrategy');

    expect($method->invoke($expander, $record, $definition))->toBeFalse();
});
