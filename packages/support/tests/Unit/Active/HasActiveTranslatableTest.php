<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\Support\Tests\Fixtures\Product;
use Moox\Support\Tests\Fixtures\ProductTranslation;

uses(RefreshDatabase::class);

it('has an active product by default', function (): void {
    $product = Product::create();
    $product->activate();
    $product->refresh();

    expect($product->is_active)->toBeTrue()
        ->and($product->isActive())->toBeTrue();
});

it('can activate and deactivate a product', function (): void {
    $product = Product::create();

    $product->deactivate();

    expect($product->fresh()->isActive())->toBeFalse();

    $product->activate();

    expect($product->fresh()->isActive())->toBeTrue();
});

it('filters active and inactive products', function (): void {
    Product::create(['is_active' => true]);
    Product::create(['is_active' => false]);

    expect(Product::active()->count())->toBe(1)
        ->and(Product::inactive()->count())->toBe(1);
});

it('has an active translation by default', function (): void {
    $product = Product::create();

    $translation = $product->translateOrNew('de_DE');
    $translation->title = 'Produkt';
    $translation->slug = 'produkt';
    $translation->is_active = true;
    $translation->save();

    expect($translation->is_active)->toBeTrue()
        ->and($translation->isActive())->toBeTrue();
});

it('can activate and deactivate a translation independently', function (): void {
    $product = Product::create();

    $translation = $product->translateOrNew('de_DE');
    $translation->title = 'Produkt';
    $translation->slug = 'produkt';
    $translation->is_active = true;
    $translation->save();

    $translation->deactivate();

    expect($translation->fresh()->isActive())->toBeFalse()
        ->and($product->fresh()->isActive())->toBeTrue();

    $translation->activate();

    expect($translation->fresh()->isActive())->toBeTrue()
        ->and($product->fresh()->isActive())->toBeTrue();
});

it('filters active and inactive translations', function (): void {
    $product = Product::create();

    foreach ([
        ['de_DE', true],
        ['en_US', false],
        ['en_GB', true],
        ['pl_PL', false],
    ] as [$locale, $active]) {
        $translation = $product->translateOrNew($locale);
        $translation->title = "Title {$locale}";
        $translation->slug = "title-{$locale}";
        $translation->is_active = $active;
        $translation->save();
    }

    expect(ProductTranslation::active()->count())->toBe(2)
        ->and(ProductTranslation::inactive()->count())->toBe(2);
});

it('keeps product and translation activity independent', function (): void {
    $product = Product::create(['is_active' => false]);

    $translation = $product->translateOrNew('de_DE');
    $translation->title = 'Produkt';
    $translation->slug = 'produkt';
    $translation->is_active = true;
    $translation->save();

    expect($product->fresh()->isActive())->toBeFalse()
        ->and($translation->fresh()->isActive())->toBeTrue();
});

it('deactivates all translations when the product is deactivated', function (): void {
    $product = Product::create();

    foreach (['de_DE', 'en_US', 'en_GB', 'pl_PL'] as $locale) {
        $translation = $product->translateOrNew($locale);
        $translation->title = "Title {$locale}";
        $translation->slug = "title-{$locale}";
        $translation->is_active = true;
    }

    $product->save();

    $product->deactivate();

    expect($product->fresh()->isActive())->toBeFalse()
        ->and($product->translations()->where('is_active', true)->count())->toBe(0)
        ->and($product->translations()->where('is_active', false)->count())->toBe(4);
});

it('activates the default translation when activating a product without active translations', function (): void {
    $defaultLocale = config('app.locale');

    $product = Product::create(['is_active' => false]);

    $translation = $product->translateOrNew($defaultLocale);
    $translation->title = 'Product';
    $translation->slug = 'product';
    $translation->is_active = false;
    $translation->save();

    $product->activate();

    expect($product->fresh()->isActive())->toBeTrue()
        ->and($product->translate($defaultLocale)->fresh()->isActive())->toBeTrue();
});

it('does not activate another translation when an active translation already exists', function (): void {
    $product = Product::create(['is_active' => false]);

    $activeTranslation = $product->translateOrNew('de_DE');
    $activeTranslation->title = 'Produkt';
    $activeTranslation->slug = 'produkt';
    $activeTranslation->is_active = true;
    $activeTranslation->save();

    $defaultLocale = config('app.locale');

    $defaultTranslation = $product->translateOrNew($defaultLocale);
    $defaultTranslation->title = 'Product';
    $defaultTranslation->slug = 'product';
    $defaultTranslation->is_active = false;
    $defaultTranslation->save();

    $product->activate();

    expect($product->fresh()->isActive())->toBeTrue()
        ->and($product->translate('de_DE')->fresh()->isActive())->toBeTrue()
        ->and($product->translate($defaultLocale)->fresh()->isActive())->toBeFalse();
});
