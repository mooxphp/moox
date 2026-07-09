<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Schema::dropIfExists('relation_target_plain');
    Schema::dropIfExists('relation_target_second');
    Schema::dropIfExists('items');

    Schema::create('relation_target_plain', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('relation_target_second', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('items', function (Blueprint $table): void {
        $table->id();
        $table->string('title')->nullable();
        $table->timestamps();
    });
});

class RelationTargetPlainModel extends Model
{
    protected $table = 'relation_target_plain';
}

class RelationTargetSecondModel extends Model
{
    protected $table = 'relation_target_second';
}

class RelationTargetPlainResource extends Resource
{
    protected static ?string $model = RelationTargetPlainModel::class;
}

// A second resource wrapping the same model as TestItemResource: it must be
// deduplicated because both point at the identical relation target.
class RelationTargetDuplicateResource extends Resource
{
    protected static ?string $model = TestItem::class;
}

// Two resources with distinct models but a colliding plural label.
class RelationCategoriesOneResource extends Resource
{
    protected static ?string $model = RelationTargetPlainModel::class;

    protected static ?string $pluralModelLabel = 'Categories';
}

class RelationCategoriesTwoResource extends Resource
{
    protected static ?string $model = RelationTargetSecondModel::class;

    protected static ?string $pluralModelLabel = 'Categories';
}

it('resolves entity keys from resources that use HasCustomFields', function (): void {
    $registry = app(EntityRegistry::class);

    expect($registry->resolveForResource(TestItemResource::class))->toBe('item')
        ->and($registry->usesCustomFields(TestItemResource::class))->toBeTrue();
});

it('returns null for resources without the trait', function (): void {
    $plainResource = new class extends Resource
    {
        protected static ?string $model = TestItem::class;
    };

    $registry = app(EntityRegistry::class);

    expect($registry->resolveForResource($plainResource::class))->toBeNull()
        ->and($registry->usesCustomFields($plainResource::class))->toBeFalse();
});

it('enumerates all panel resources as relation targets, not just custom field ones', function (): void {
    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [TestItemResource::class, RelationTargetPlainResource::class];
        }
    };

    $resources = $registry->relatableResources();

    expect($resources)->toHaveKey('item')
        ->and($resources['item'])->toBe(TestItemResource::class)
        ->and($resources)->toHaveKey('relation_target_plain')
        ->and($resources['relation_target_plain'])->toBe(RelationTargetPlainResource::class)
        ->and($registry->relatedModelFor('item'))->toBe(TestItem::class)
        ->and($registry->relatedResourceFor('relation_target_plain'))->toBe(RelationTargetPlainResource::class)
        ->and($registry->relatableOptions())->toHaveKeys(['item', 'relation_target_plain']);
});

it('deduplicates resources that share the same model', function (): void {
    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [TestItemResource::class, RelationTargetDuplicateResource::class];
        }
    };

    $resources = collect($registry->relatableResources())
        ->filter(fn (string $resource): bool => $resource::getModel() === TestItem::class);

    expect($resources)->toHaveCount(1);
});

it('qualifies options that would otherwise share a label', function (): void {
    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [RelationCategoriesOneResource::class, RelationCategoriesTwoResource::class];
        }
    };

    $labels = array_values($registry->relatableOptions());

    expect($labels)->toHaveCount(2)
        ->and($labels)->each->toStartWith('Categories (')
        ->and(count(array_unique($labels)))->toBe(2);
});

it('excludes resources whose database table is missing', function (): void {
    Schema::dropIfExists('relation_target_second');

    $registry = new class extends EntityRegistry
    {
        protected function panelResources(): array
        {
            return [RelationTargetPlainResource::class, RelationCategoriesTwoResource::class];
        }
    };

    expect($registry->relatableResources())->toHaveKey('relation_target_plain')
        ->and($registry->relatableResources())->not->toHaveKey('relation_target_second');
});

it('memoizes modelIsQueryable schema checks', function (): void {
    $registry = app(EntityRegistry::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($registry->modelIsQueryable(TestItem::class))->toBeTrue();

    $queriesAfterFirst = count(DB::getQueryLog());

    expect($registry->modelIsQueryable(TestItem::class))->toBeTrue()
        ->and(count(DB::getQueryLog()))->toBe($queriesAfterFirst);
});

it('memoizes database table column checks', function (): void {
    $registry = app(EntityRegistry::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($registry->databaseTableHasColumn('items', 'title'))->toBeTrue();

    $queriesAfterFirst = count(DB::getQueryLog());

    expect($registry->databaseTableHasColumn('items', 'title'))->toBeTrue()
        ->and(count(DB::getQueryLog()))->toBe($queriesAfterFirst);
});
