<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Support\Scopes\ScopeQuery;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::create('scope_query_items', function (Blueprint $table): void {
        $table->id();
        $table->string('scope')->nullable();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('scope_query_items');
});

it('applyUnassigned keeps only null and empty scope rows', function (): void {
    $model = new class extends Model
    {
        protected $table = 'scope_query_items';

        public $timestamps = false;

        protected $guarded = [];
    };

    $model::query()->insert([
        ['id' => 1, 'scope' => null],
        ['id' => 2, 'scope' => ''],
        ['id' => 3, 'scope' => 'media:tag:blog:private'],
    ]);

    $ids = ScopeQuery::applyUnassigned($model::query())
        ->orderBy('id')
        ->pluck('id')
        ->all();

    expect($ids)->toBe([1, 2]);
});
