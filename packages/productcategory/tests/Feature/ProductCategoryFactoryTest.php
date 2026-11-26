<?php

declare(strict_types=1);

use Moox\ProductCategory\Models\ProductCategory;
use Moox\ProductCategory\Models\ProductCategoryTranslation;

it('creates a product category with translatable attributes for the app locale', function (): void {
    $locale = app()->getLocale();

    $productCategory = ProductCategory::factory()->create();

    expect($productCategory->is_active)->toBeTrue();
    expect($productCategory->translations)->toHaveCount(1);

    $translation = ProductCategoryTranslation::query()
        ->whereBelongsTo($productCategory)
        ->firstOrFail();

    expect($translation->locale)->toBe($locale);
    expect($translation->title)->not->toBeEmpty();
    expect($translation->slug)->not->toBeEmpty();
});

it('can create a product category without translation rows', function (): void {
    $productCategory = ProductCategory::factory()->withoutTranslations()->create();

    expect($productCategory->translations()->count())->toBe(0);
    expect($productCategory->is_active)->toBeTrue();
});

it('creates translations from explicit attributes merged with defaults', function (): void {
    $productCategory = ProductCategory::factory()->withTranslationAttributes([
        'en' => [
            'title' => 'English product category',
            'slug' => 'english-product-category',
        ],
        'de' => [
            'title' => 'Deutsche Produktkategorie',
            'slug' => 'deutsche-produktkategorie',
        ],
    ])->create();

    expect($productCategory->translations)->toHaveCount(2);

    $en = ProductCategoryTranslation::query()
        ->whereBelongsTo($productCategory)
        ->where('locale', 'en')
        ->firstOrFail();
    expect($en->title)->toBe('English product category');
    expect($en->slug)->toBe('english-product-category');

    $de = ProductCategoryTranslation::query()
        ->whereBelongsTo($productCategory)
        ->where('locale', 'de')
        ->firstOrFail();
    expect($de->title)->toBe('Deutsche Produktkategorie');
    expect($de->slug)->toBe('deutsche-produktkategorie');
});

it('can assign a parent product category', function (): void {
    $parent = ProductCategory::factory()->withoutTranslations()->create();
    $child = ProductCategory::factory()->withoutTranslations()->create([
        'parent_id' => $parent->id,
    ]);

    expect($child->parent?->is($parent))->toBeTrue();
    expect($parent->children)->toHaveCount(1);
});
