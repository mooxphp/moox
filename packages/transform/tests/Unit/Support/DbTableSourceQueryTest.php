<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Transform\Support\DbTableSourceQuery;
use Tests\TestCase;

uses(TestCase::class);

test('db table source query applies datetime_gte as greater-than-or-equal on default drivers', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->id();
        $table->timestamp('changed_at')->nullable();
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'changed_at' => '2026-08-10 00:00:00'],
        ['id' => 2, 'changed_at' => '2026-08-18 00:00:00'],
    ]);

    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'column' => 'changed_at',
                'operator' => 'datetime_gte',
                'value' => '2026-08-17 00:00:00',
            ],
        ],
    ]);

    expect($query->orderBy('id')->pluck('id')->all())->toBe([2]);

    Schema::dropIfExists('db_table_source_rows');
});

test('db table source query applies raw where clauses with bindings', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->id();
        $table->timestamp('changed_at')->nullable();
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'changed_at' => '2026-08-10 00:00:00'],
        ['id' => 2, 'changed_at' => '2026-08-18 00:00:00'],
    ]);

    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'operator' => 'raw',
                'value' => [
                    'sql' => 'changed_at >= ?',
                    'bindings' => ['2026-08-17 00:00:00'],
                ],
            ],
        ],
    ]);

    expect($query->orderBy('id')->pluck('id')->all())->toBe([2]);

    Schema::dropIfExists('db_table_source_rows');
});

test('db table source query compiles in as sql in not or equalities', function (): void {
    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'column' => 'country_id',
                'operator' => 'in',
                'value' => [1, 2, 3],
            ],
        ],
    ]);

    expect($query->toSql())->toContain(' in (')
        ->and($query->toSql())->not->toContain(' or ')
        ->and($query->getBindings())->toBe([1, 2, 3]);
});

test('db table source query compiles in with null as sql in plus is null', function (): void {
    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'column' => 'is_deleted',
                'operator' => 'in',
                'value' => [null, 0, false],
            ],
        ],
    ]);

    $sql = $query->toSql();

    expect($sql)->toContain(' in (')
        ->and($sql)->toContain(' is null')
        ->and($sql)->not->toContain(' = ? or ')
        ->and($query->getBindings())->toBe([0]);
});

test('db table source query compiles in with only null as is null', function (): void {
    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'column' => 'is_deleted',
                'operator' => 'in',
                'value' => [null],
            ],
        ],
    ]);

    expect($query->toSql())->toContain(' is null')
        ->and($query->toSql())->not->toContain(' in (')
        ->and($query->getBindings())->toBe([]);
});

test('db table source query applies in to match listed ids', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->id();
        $table->integer('country_id')->nullable();
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'country_id' => 1],
        ['id' => 2, 'country_id' => 3],
        ['id' => 3, 'country_id' => 99],
        ['id' => 4, 'country_id' => null],
    ]);

    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'column' => 'country_id',
                'operator' => 'in',
                'value' => [1, 3],
            ],
        ],
    ]);

    expect($query->orderBy('id')->pluck('id')->all())->toBe([1, 2]);

    Schema::dropIfExists('db_table_source_rows');
});

test('db table source query applies in with null to match listed values or null', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->id();
        $table->integer('is_deleted')->nullable();
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'is_deleted' => null],
        ['id' => 2, 'is_deleted' => 0],
        ['id' => 3, 'is_deleted' => 1],
    ]);

    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'column' => 'is_deleted',
                'operator' => 'in',
                'value' => [null, 0, false],
            ],
        ],
    ]);

    expect($query->orderBy('id')->pluck('id')->all())->toBe([1, 2]);

    Schema::dropIfExists('db_table_source_rows');
});

test('db table source query applies in inside or groups', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->id();
        $table->integer('country_id')->nullable();
        $table->integer('type')->nullable();
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'country_id' => 1, 'type' => 9],
        ['id' => 2, 'country_id' => 99, 'type' => 2],
        ['id' => 3, 'country_id' => 99, 'type' => 9],
    ]);

    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'operator' => 'or',
                'value' => [
                    [
                        'column' => 'country_id',
                        'operator' => 'in',
                        'value' => [1, 2],
                    ],
                    [
                        'column' => 'type',
                        'operator' => 'in',
                        'value' => [2],
                    ],
                ],
            ],
        ],
    ]);

    expect($query->toSql())->toContain(' in (')
        ->and($query->toSql())->not->toContain(' = ? or ')
        ->and($query->orderBy('id')->pluck('id')->all())->toBe([1, 2]);

    Schema::dropIfExists('db_table_source_rows');
});

test('db table source query applies or groups for multi-column datetime cutoffs', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->id();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('created_at')->nullable();
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'updated_at' => '2026-08-10 00:00:00', 'created_at' => '2026-08-01 00:00:00'],
        ['id' => 2, 'updated_at' => '2026-08-17 12:00:00', 'created_at' => '2026-08-01 00:00:00'],
        ['id' => 3, 'updated_at' => null, 'created_at' => '2026-08-18 00:00:00'],
    ]);

    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'operator' => 'or',
                'value' => [
                    [
                        'column' => 'updated_at',
                        'operator' => 'datetime_gte',
                        'value' => '2026-08-17 00:00:00',
                    ],
                    [
                        'column' => 'created_at',
                        'operator' => 'datetime_gte',
                        'value' => '2026-08-17 00:00:00',
                    ],
                ],
            ],
        ],
    ]);

    expect($query->orderBy('id')->pluck('id')->all())->toBe([2, 3]);

    Schema::dropIfExists('db_table_source_rows');
});

test('ordered chunk returns the same rows as offset pagination for unique keys', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->integer('id')->primary();
        $table->string('name');
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => -2, 'name' => 'a'],
        ['id' => 0, 'name' => 'b'],
        ['id' => 1, 'name' => 'c'],
        ['id' => 4, 'name' => 'd'],
        ['id' => 5, 'name' => 'e'],
        ['id' => 9, 'name' => 'f'],
        ['id' => 10, 'name' => 'g'],
    ]);

    $chunks = [];
    foreach (DbTableSourceQuery::orderedChunk(DB::table('db_table_source_rows'), 'id', 3) as $chunk) {
        $chunks[] = array_column($chunk, 'id');
    }

    expect($chunks)->toBe([
        [-2, 0, 1],
        [4, 5, 9],
        [10],
    ]);

    Schema::dropIfExists('db_table_source_rows');
});

test('ordered chunk includes a leading null key then continues past it', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->id();
        $table->integer('legacy_id')->nullable();
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'legacy_id' => null],
        ['id' => 2, 'legacy_id' => 10],
        ['id' => 3, 'legacy_id' => 20],
        ['id' => 4, 'legacy_id' => 30],
    ]);

    $chunks = [];
    foreach (DbTableSourceQuery::orderedChunk(DB::table('db_table_source_rows'), 'legacy_id', 2) as $chunk) {
        $chunks[] = array_column($chunk, 'legacy_id');
    }

    expect($chunks)->toBe([
        [null, 10],
        [20, 30],
    ]);

    Schema::dropIfExists('db_table_source_rows');
});

test('ordered chunk keeps existing where filters and the same filtered order', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->integer('id')->primary();
        $table->integer('country_id');
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'country_id' => 1],
        ['id' => 2, 'country_id' => 99],
        ['id' => 3, 'country_id' => 1],
        ['id' => 4, 'country_id' => 1],
        ['id' => 5, 'country_id' => 99],
        ['id' => 6, 'country_id' => 1],
    ]);

    $query = DB::table('db_table_source_rows');
    DbTableSourceQuery::applyWhereClauses($query, [
        'where' => [
            [
                'column' => 'country_id',
                'operator' => 'in',
                'value' => [1],
            ],
        ],
    ]);

    $ids = [];
    foreach (DbTableSourceQuery::orderedChunk($query, 'id', 2) as $chunk) {
        foreach ($chunk as $row) {
            $ids[] = $row['id'];
        }
    }

    expect($ids)->toBe([1, 3, 4, 6]);

    Schema::dropIfExists('db_table_source_rows');
});

test('ordered chunk seeks by key instead of offset after the first page', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->integer('id')->primary();
        $table->string('name');
    });

    DB::table('db_table_source_rows')->insert([
        ['id' => 1, 'name' => 'a'],
        ['id' => 2, 'name' => 'b'],
        ['id' => 3, 'name' => 'c'],
        ['id' => 4, 'name' => 'd'],
        ['id' => 5, 'name' => 'e'],
    ]);

    $sql = [];
    DB::listen(static function (object $query) use (&$sql): void {
        $sql[] = $query->sql;
    });

    iterator_to_array(DbTableSourceQuery::orderedChunk(DB::table('db_table_source_rows'), 'id', 2));

    expect($sql)->not->toBeEmpty();

    foreach ($sql as $statement) {
        expect($statement)->not->toContain('offset');
    }

    expect($sql[1] ?? '')->toContain(' > ');

    Schema::dropIfExists('db_table_source_rows');
});

test('ordered chunk yields nothing for an empty table', function (): void {
    Schema::dropIfExists('db_table_source_rows');
    Schema::create('db_table_source_rows', function (Blueprint $table): void {
        $table->integer('id')->primary();
    });

    $chunks = iterator_to_array(DbTableSourceQuery::orderedChunk(DB::table('db_table_source_rows'), 'id', 10));

    expect($chunks)->toBe([]);

    Schema::dropIfExists('db_table_source_rows');
});
