<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Feature;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Definition\Laravel\Migration;
use Moox\Definition\Laravel\WriteOwnershipException;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Tests\Support\ArticleCatalog;
use Moox\Definition\Tests\Support\ArticleRecord;
use Moox\Definition\Tests\Support\ProductGroupRecord;

beforeEach(function (): void {
    $catalog = ArticleCatalog::catalog();
    $migration = new Migration;

    Schema::create('product_groups', function (Blueprint $table) use ($catalog, $migration): void {
        $migration->apply($table, $catalog->resolve('moox/product-group', 'productGroup'), $catalog);
    });
    Schema::create('articles', function (Blueprint $table) use ($catalog, $migration): void {
        $migration->apply($table, $catalog->resolve('moox/article', 'article'), $catalog);
    });
});

it('generates identifiers and refuses mass assignment of system-managed fields', function (): void {
    $group = new ProductGroupRecord;
    $group->save();

    $article = new ArticleRecord;
    $article->fill([
        'article_number' => 'A-1',
        'notes' => 'Editable feature field',
        'uuid' => '00000000-0000-0000-0000-000000000000',
        'ulid' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
    ]);
    $article->productGroup()->associate($group);
    $article->save();

    expect($article->isFillable('article_number'))->toBeTrue()
        ->and($article->isFillable('notes'))->toBeTrue()
        ->and($article->isFillable('uuid'))->toBeFalse()
        ->and($article->isFillable('id'))->toBeFalse()
        ->and($article->uuid)->not->toBe('00000000-0000-0000-0000-000000000000')
        ->and($article->uuid)->toHaveLength(36)
        ->and($article->ulid)->not->toBe('01ARZ3NDEKTSV4RRFFQ69G5FAV')
        ->and($article->ulid)->toHaveLength(26)
        ->and($article->id)->toBeInt()
        ->and($article->notes)->toBe('Editable feature field')
        ->and($article->product_group_ulid)->toBe($group->ulid)
        ->and($article->productGroup->is($group))->toBeTrue();
});

it('stores a dateTime instant in UTC', function (): void {
    $group = new ProductGroupRecord;
    $group->save();
    $article = new ArticleRecord;
    $article->article_number = 'A-2';
    $article->available_at = new DateTimeImmutable('2026-10-04 12:00:00', new DateTimeZone('Europe/Berlin'));
    $article->productGroup()->associate($group);
    $article->save();
    $article->refresh();

    expect($article->available_at)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($article->available_at->format('Y-m-d H:i:s'))->toBe('2026-10-04 10:00:00')
        ->and($article->available_at->getTimezone()->getName())->toBe('UTC');
});

it('rejects changes to immutable and system-managed fields', function (): void {
    $group = new ProductGroupRecord;
    $group->save();
    $article = new ArticleRecord;
    $article->article_number = 'A-3';
    $article->productGroup()->associate($group);
    $article->save();
    $article->uuid = '11111111-1111-1111-1111-111111111111';

    expect(fn () => $article->save())->toThrow(WriteOwnershipException::class, 'immutable');

    $article->refresh();
    $article->notes = 'Changed by the user';
    $article->save();

    expect($article->fresh()->notes)->toBe('Changed by the user');
});

it('reports a missing model binding', function (): void {
    $article = new class extends ArticleRecord
    {
        protected static function modelBindings(): array
        {
            return [];
        }
    };

    expect(fn () => $article->productGroup())->toThrow(IncompleteProjection::class, 'Laravel model class for relation [productGroup] is not configured.');
});
