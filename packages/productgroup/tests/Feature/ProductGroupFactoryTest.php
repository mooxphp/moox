<?php

declare(strict_types=1);

use Moox\ProductGroup\Models\ProductGroup;
use Moox\ProductGroup\Models\ProductGroupTranslation;

it('creates a product group with translatable attributes for the app locale', function (): void {
    $locale = app()->getLocale();

    $productGroup = ProductGroup::factory()->create();

    expect($productGroup->is_active)->toBeTrue();
    expect($productGroup->translations)->toHaveCount(1);

    $translation = ProductGroupTranslation::query()
        ->whereBelongsTo($productGroup)
        ->firstOrFail();

    expect($translation->locale)->toBe($locale);
    expect($translation->title)->not->toBeEmpty();
    expect($translation->slug)->not->toBeEmpty();
});

it('can create a product group without translation rows', function (): void {
    $productGroup = ProductGroup::factory()->withoutTranslations()->create();

    expect($productGroup->translations()->count())->toBe(0);
    expect($productGroup->is_active)->toBeTrue();
});

it('creates translations from explicit attributes merged with defaults', function (): void {
    $productGroup = ProductGroup::factory()->withTranslationAttributes([
        'en' => [
            'title' => 'English product group',
            'slug' => 'english-product-group',
        ],
        'de' => [
            'title' => 'Deutsche Produktgruppe',
            'slug' => 'deutsche-produktgruppe',
        ],
    ])->create();

    expect($productGroup->translations)->toHaveCount(2);

    $en = ProductGroupTranslation::query()
        ->whereBelongsTo($productGroup)
        ->where('locale', 'en')
        ->firstOrFail();
    expect($en->title)->toBe('English product group');
    expect($en->slug)->toBe('english-product-group');

    $de = ProductGroupTranslation::query()
        ->whereBelongsTo($productGroup)
        ->where('locale', 'de')
        ->firstOrFail();
    expect($de->title)->toBe('Deutsche Produktgruppe');
    expect($de->slug)->toBe('deutsche-produktgruppe');
});

it('can assign a parent product group', function (): void {
    $parent = ProductGroup::factory()->withoutTranslations()->create();
    $child = ProductGroup::factory()->withoutTranslations()->create([
        'parent_id' => $parent->id,
    ]);

    expect($child->parent?->is($parent))->toBeTrue();
    expect($parent->children)->toHaveCount(1);
});
