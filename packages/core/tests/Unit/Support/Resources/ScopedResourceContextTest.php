<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Models\Concerns\HasScopedModel;
use Moox\Core\Models\Scope;
use Moox\Core\Support\Resources\ScopedResourceContext;
use Tests\TestCase;

uses(TestCase::class);

it('does not treat the scopes catalog identity column as an assignment filter', function (): void {
    $resource = new class
    {
        public static function getConfiguration(): mixed
        {
            return null;
        }
    };

    $before = Scope::query()->toSql();
    $after = ScopedResourceContext::applyScope(Scope::query(), $resource::class)->toSql();

    expect($after)->toBe($before)
        ->and($after)->not->toContain('is null');
});

it('does not restrict global HasScopedModel lists by scope', function (): void {
    Schema::create('scoped_context_items', function (Blueprint $table): void {
        $table->id();
        $table->string('scope')->nullable();
    });

    $model = new class extends Model
    {
        use HasScopedModel;

        protected $table = 'scoped_context_items';

        public $timestamps = false;

        protected $guarded = [];
    };

    $model::query()->insert([
        ['id' => 1, 'scope' => null],
        ['id' => 2, 'scope' => 'media:tag:blog:private'],
    ]);

    $resource = new class
    {
        public static function getConfiguration(): mixed
        {
            return null;
        }
    };

    $ids = ScopedResourceContext::applyScope($model::query(), $resource::class)
        ->orderBy('id')
        ->pluck('id')
        ->all();

    expect($ids)->toBe([1, 2]);

    Schema::dropIfExists('scoped_context_items');
});
