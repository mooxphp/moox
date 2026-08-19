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
