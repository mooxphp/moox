<?php

declare(strict_types=1);

use Moox\ProductGroup\Models\ProductGroup;

it('creates a product group with translatable attributes for the app locale', function (): void {
    $locale = (string) config('app.locale', 'en');

    $productGroup = ProductGroup::factory()->create();

    expect($productGroup->code)->not->toBeEmpty();
    expect($productGroup->type)->toBe('family');
    expect($productGroup->status)->not->toBeEmpty();
    expect($productGroup->translations)->toHaveCount(1);

    $translation = $productGroup->translations->first();
    expect($translation?->locale)->toBe($locale);
    expect($translation?->name)->not->toBeEmpty();
    expect($translation?->slug)->not->toBeEmpty();
    expect($translation?->description)->not->toBeEmpty();
});

it('can create a product group without translation rows', function (): void {
    $productGroup = ProductGroup::factory()->withoutTranslations()->create();

    expect($productGroup->translations()->count())->toBe(0);
    expect($productGroup->custom_properties)->toBeArray();
});

it('creates translations from explicit attributes merged with defaults', function (): void {
    $productGroup = ProductGroup::factory()->withTranslationAttributes([
        'en' => [
            'name' => 'English product group',
            'slug' => 'english-product-group',
        ],
        'de' => [
            'name' => 'Deutsche Produktgruppe',
            'slug' => 'deutsche-produktgruppe',
        ],
    ])->create();

    expect($productGroup->translations)->toHaveCount(2);

    $en = $productGroup->translations->firstWhere('locale', 'en');
    expect($en?->name)->toBe('English product group');
    expect($en?->slug)->toBe('english-product-group');

    $de = $productGroup->translations->firstWhere('locale', 'de');
    expect($de?->name)->toBe('Deutsche Produktgruppe');
    expect($de?->slug)->toBe('deutsche-produktgruppe');
});

it('can assign a parent product group', function (): void {
    $parent = ProductGroup::factory()->withoutTranslations()->create();
    $child = ProductGroup::factory()->withoutTranslations()->create([
        'parent_id' => $parent->id,
    ]);

    expect($child->parent?->is($parent))->toBeTrue();
    expect($parent->children)->toHaveCount(1);
});
