<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Moox\Product\Enums\ProductType;
use Moox\Product\Models\Product;
use Moox\Product\Models\ProductTranslation;

it('creates a product with translatable attributes for the app locale', function (): void {
    $locale = app()->getLocale();

    $product = Product::factory()->create();

    expect($product->type)->toBe(ProductType::Simple);
    expect($product->sku)->not->toBeEmpty();
    expect($product->is_orderable)->toBeFalse();
    expect($product->translations)->toHaveCount(1);

    $translation = ProductTranslation::query()
        ->whereBelongsTo($product)
        ->firstOrFail();

    expect($translation->locale)->toBe($locale);
    expect($translation->title)->not->toBeEmpty();
    expect($translation->slug)->not->toBeEmpty();
});

it('can create a product without translation rows', function (): void {
    $product = Product::factory()->withoutTranslations()->create();

    expect($product->translations()->count())->toBe(0);
    expect($product->type)->toBe(ProductType::Simple);
});

it('creates translations from explicit attributes merged with defaults', function (): void {
    $product = Product::factory()->withTranslationAttributes([
        'en' => [
            'title' => 'English product',
            'slug' => 'english-product',
        ],
        'de' => [
            'title' => 'Deutsches Produkt',
            'slug' => 'deutsches-produkt',
        ],
    ])->create();

    expect($product->translations)->toHaveCount(2);

    $en = ProductTranslation::query()
        ->whereBelongsTo($product)
        ->where('locale', 'en')
        ->firstOrFail();
    expect($en->title)->toBe('English product');
    expect($en->slug)->toBe('english-product');

    $de = ProductTranslation::query()
        ->whereBelongsTo($product)
        ->where('locale', 'de')
        ->firstOrFail();
    expect($de->title)->toBe('Deutsches Produkt');
    expect($de->slug)->toBe('deutsches-produkt');
});

it('requires a sku for a simple product', function (): void {
    expect(fn () => Product::factory()->withoutTranslations()->create([
        'sku' => null,
    ]))->toThrow(ValidationException::class);
});

it('stores a grouped product without a sku', function (): void {
    $product = Product::factory()->grouped()->withoutTranslations()->create();

    expect($product->type)->toBe(ProductType::Grouped);
    expect($product->sku)->toBeNull();
    expect($product->is_orderable)->toBeFalse();
    expect($product->is_canonical)->toBeFalse();
    expect($product->canonical_product_id)->toBeNull();
});
it('stores an umlaut sku next to the same letters without the umlaut', function (): void {
    Product::factory()->withoutTranslations()->create([
        'sku' => 'BO-026-030-9',
    ]);

    $withUmlaut = Product::factory()->withoutTranslations()->create([
        'sku' => 'BÖ-026-030-9',
    ]);

    expect($withUmlaut->sku)->toBe('BÖ-026-030-9')
        ->and($withUmlaut->skuTaken())->toBeFalse();
});

it('rejects a duplicate slug in the same product category and locale', function (): void {
    Product::factory()->withTranslationAttributes([
        'en' => [
            'title' => 'First',
            'slug' => 'shared-slug',
        ],
    ])->create([
        'product_category_id' => 5,
    ]);

    expect(fn () => Product::factory()->withTranslationAttributes([
        'en' => [
            'title' => 'Second',
            'slug' => 'shared-slug',
        ],
    ])->create([
        'product_category_id' => 5,
    ]))->toThrow(ValidationException::class);
});
