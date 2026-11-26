<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Moox\Product\Enums\ProductType;
use Moox\Product\Models\Product;
use Moox\Product\Models\Variant;
use Moox\Product\Models\VariantTranslation;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a variant on a simple product with a translation and audit fields', function (): void {
    $locale = app()->getLocale();

    $variant = Variant::factory()->create();
    $product = $variant->product;

    expect($product)->toBeInstanceOf(Product::class);
    expect($product instanceof Product ? $product->type : null)->toBe(ProductType::Simple);
    expect($variant->is_orderable)->toBeFalse();
    expect($variant->ulid)->not->toBeEmpty();
    expect($variant->uuid)->not->toBeEmpty();

    $translation = VariantTranslation::query()
        ->whereBelongsTo($variant)
        ->firstOrFail();

    expect($translation->locale)->toBe($locale);
    expect($translation->title)->not->toBeEmpty();
    expect($translation->created_by_id)->toBe(1);
    expect($translation->updated_by_id)->toBe(1);
    expect($translation->created_at)->not->toBeNull();
    expect($translation->updated_at)->not->toBeNull();
    expect($translation->published_at)->toBeNull();
    expect($translation->unpublished_at)->toBeNull();
    expect($translation->deleted_at)->toBeNull();
});

it('reads an empty sku from the simple product and keeps the column empty', function (): void {
    $product = Product::factory()->withoutTranslations()->create([
        'sku' => 'PARENT-SKU',
        'note' => 'From product',
        'is_orderable' => true,
        'external_reference' => 'EXT-1',
    ]);

    $variant = Variant::factory()->withoutTranslations()->create([
        'product_id' => $product->getKey(),
        'sku' => null,
        'note' => null,
        'external_reference' => null,
    ]);

    expect($variant->getAttributes()['sku'])->toBeNull()
        ->and($variant->sku)->toBe('PARENT-SKU')
        ->and($variant->getAttributes()['note'])->toBeNull()
        ->and($variant->note)->toBe('From product')
        ->and($variant->external_reference)->toBe('EXT-1')
        ->and($variant->is_orderable)->toBeFalse();
});

it('stores the product sku as empty because the variant reads it from the product', function (): void {
    $product = Product::factory()->withoutTranslations()->create([
        'sku' => 'PARENT-SKU',
    ]);

    $variant = Variant::factory()->withoutTranslations()->create([
        'product_id' => $product->getKey(),
        'sku' => 'PARENT-SKU',
    ]);

    expect($variant->getAttributes()['sku'])->toBeNull()
        ->and($variant->sku)->toBe('PARENT-SKU');
});

it('stores a sku that belongs to another product', function (): void {
    Product::factory()->withoutTranslations()->create([
        'sku' => 'OTHER-SKU',
    ]);

    $variant = Variant::factory()->withoutTranslations()->create([
        'sku' => 'OTHER-SKU',
    ]);

    expect($variant->getAttributes()['sku'])->toBe('OTHER-SKU')
        ->and($variant->sku)->toBe('OTHER-SKU');
});

it('does not inherit manufacturer fields', function (): void {
    $product = Product::factory()->withoutTranslations()->create([
        'manufacturer_id' => 9,
    ]);

    $variant = Variant::factory()->withoutTranslations()->create([
        'product_id' => $product->getKey(),
        'manufacturer_id' => null,
        'manufacturer_name' => null,
    ]);

    expect($variant->manufacturer_id)->toBeNull()
        ->and($variant->manufacturer_name)->toBeNull();
});

it('reads an empty translation from the simple product', function (): void {
    $locale = app()->getLocale();

    $product = Product::factory()->withTranslationAttributes([
        $locale => [
            'title' => 'Parent title',
            'slug' => 'parent-title',
        ],
    ])->create();

    $variant = Variant::factory()->withTranslationAttributes([
        $locale => [
            'title' => null,
            'slug' => null,
        ],
    ])->create([
        'product_id' => $product->getKey(),
    ]);

    $translation = VariantTranslation::query()->whereBelongsTo($variant)->where('locale', $locale)->firstOrFail();

    expect($translation->getAttributes()['title'])->toBeNull()
        ->and($variant->title)->toBe('Parent title')
        ->and($variant->slug)->toBe('parent-title');
});

it('stores manufacturer id and name together', function (): void {
    $variant = Variant::factory()->withoutTranslations()->create([
        'manufacturer_id' => 12,
        'manufacturer_name' => 'Acme',
    ]);

    expect($variant->manufacturer_id)->toBe(12);
    expect($variant->manufacturer_name)->toBe('Acme');
});

it('rejects a variant on a grouped product', function (): void {
    $product = Product::factory()->grouped()->withoutTranslations()->create();

    expect(fn () => Variant::factory()->withoutTranslations()->create([
        'product_id' => $product->getKey(),
    ]))->toThrow(ValidationException::class);
});

it('rejects a variant sku that another variant already uses', function (): void {
    $product = Product::factory()->withoutTranslations()->create();

    Variant::factory()->withoutTranslations()->create([
        'product_id' => $product->getKey(),
        'sku' => 'TAKEN-SKU',
    ]);

    expect(fn () => Variant::factory()->withoutTranslations()->create([
        'sku' => 'TAKEN-SKU',
    ]))->toThrow(ValidationException::class);
});
