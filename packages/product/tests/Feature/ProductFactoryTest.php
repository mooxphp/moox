<?php

declare(strict_types=1);

use Moox\Product\Models\Product;

it('creates a product with translatable attributes for the app locale', function (): void {
    $locale = (string) config('app.locale', 'en');

    $product = Product::factory()->create();

    expect($product->sku)->not->toBeEmpty();
    expect($product->price)->not->toBeNull();
    expect($product->stock)->toBeInt();
    expect($product->translations)->toHaveCount(1);

    $translation = $product->translations->first();
    expect($translation?->locale)->toBe($locale);
    expect($translation?->name)->not->toBeEmpty();
    expect($translation?->slug)->not->toBeEmpty();
    expect($translation?->description)->not->toBeEmpty();
});

it('can create a product without translation rows', function (): void {
    $product = Product::factory()->withoutTranslations()->create();

    expect($product->translations()->count())->toBe(0);
    expect($product->meta)->toBeArray();
});

it('creates translations from explicit attributes merged with defaults', function (): void {
    $product = Product::factory()->withTranslationAttributes([
        'en' => [
            'name' => 'English product',
            'slug' => 'english-product',
        ],
        'de' => [
            'name' => 'Deutsches Produkt',
            'slug' => 'deutsches-produkt',
        ],
    ])->create();

    expect($product->translations)->toHaveCount(2);

    $en = $product->translations->firstWhere('locale', 'en');
    expect($en?->name)->toBe('English product');
    expect($en?->slug)->toBe('english-product');

    $de = $product->translations->firstWhere('locale', 'de');
    expect($de?->name)->toBe('Deutsches Produkt');
    expect($de?->slug)->toBe('deutsches-produkt');
});
