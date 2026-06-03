<?php

declare(strict_types=1);

use Moox\Transform\Models\TransformDefinition;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';

uses(TestCase::class);

test('it discovers configured database connections via model helper', function (): void {
    createTestTables();

    config()->set('database.connections.transform_fake', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    $options = TransformDefinition::discoverConnectionOptions();

    expect($options)->toHaveKey((string) config('database.default'));
    expect($options)->toHaveKey('transform_fake');
});

test('it discovers only current database tables via model helper', function (): void {
    createTestTables();

    $tables = TransformDefinition::discoverTableOptions((string) config('database.default'));

    expect($tables)->toHaveKey('transform_definitions');
    expect($tables)->toHaveKey('transform_records');
    expect($tables)->toHaveKey('transform_dummy_models');
    expect(collect(array_keys($tables))->contains(
        fn (string $table): bool => str_contains($table, '.')
    ))->toBeFalse();
});

test('it discovers table columns via model helper', function (): void {
    createTestTables();

    $columns = TransformDefinition::discoverColumnOptions((string) config('database.default'), 'transform_dummy_models');

    expect($columns)->toHaveKey('id');
    expect($columns)->toHaveKey('title');
    expect($columns)->toHaveKey('stock');
    expect($columns)->toHaveKey('price_label');
});

test('it blocks schema qualified table names in column discovery helper', function (): void {
    createTestTables();

    $columns = TransformDefinition::discoverColumnOptions((string) config('database.default'), 'otherdb.transform_dummy_models');

    expect($columns)->toBe([]);
});
