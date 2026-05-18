<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Moox\Attribute\Models\Attribute;
use Moox\Attribute\Models\AttributeValues;
use Moox\Product\Models\Product;

it('creates a product with one translation for the app locale', function (): void {
    $locale = (string) config('app.locale', 'en');

    $product = Product::factory()->create();

    expect($product->uuid)->not->toBeEmpty();
    expect($product->ulid)->not->toBeEmpty();
    expect($product->translations)->toHaveCount(1);
    expect($product->translations->first()?->locale)->toBe($locale);
    expect($product->translations->first()?->title)->not->toBeEmpty();
    expect($product->translations->first()?->slug)->not->toBeEmpty();
    expect($product->translations->first()?->permalink)->not->toBeEmpty();
});

it('can create a product without translation rows', function (): void {
    $product = Product::factory()->withoutTranslations()->create();

    expect($product->translations()->count())->toBe(0);
    $product->load('attributeValues.attribute');
    expect($product->attributeValues)->toHaveCount(1);
});

it('creates a product with an attribute, value, and pivot link', function (): void {
    $product = Product::factory()->create();

    $product->load('attributeValues.attribute.translations');

    expect($product->attributeValues)->toHaveCount(1);
    expect($product->attributeValues->first()?->attribute)->not->toBeNull();
    expect($product->attributeValues->first()?->value)->toBeArray();
    expect($product->attributeValues->first()?->attribute?->translations)->toHaveCount(1);
});

it('can create a product without attached attribute values', function (): void {
    $product = Product::factory()->withoutAttributeValues()->create();

    expect($product->attributeValues()->count())->toBe(0);
    expect($product->translations)->toHaveCount(1);
});

it('creates many products each with their own translation', function (): void {
    $locale = (string) config('app.locale', 'en');

    $products = Product::factory()->count(5)->create();

    expect($products)->toHaveCount(5);

    foreach ($products as $product) {
        expect($product->translations)->toHaveCount(1);
        expect($product->translations->first()?->locale)->toBe($locale);
    }
});

it('creates a product with multiple faked translation locales', function (): void {
    $product = Product::factory()->withTranslationLocales('de', 'en')->create();

    expect($product->translations->pluck('locale')->sort()->values()->all())->toBe(['de', 'en']);

    foreach (['de', 'en'] as $locale) {
        $row = $product->translations->firstWhere('locale', $locale);
        expect($row)->not->toBeNull();
        expect($row->title)->not->toBeEmpty();
        expect($row->slug)->not->toBeEmpty();
        expect($row->permalink)->not->toBeEmpty();
    }
});

it('can attach random attribute values to random products with plain loops', function (): void {
    $attribute = Attribute::query()->create([
        'type' => 'text',
        'name' => 'Seeder color',
        'description' => 'Test attribute',
        'status' => 'draft',
        'uuid' => (string) Str::uuid(),
        'ulid' => (string) Str::ulid(),
    ]);

    $attribute->translateOrNew($locale)
        ->fill([
            'value' => ['label' => 'Color'],
            'translation_status' => 'draft',
        ])
        ->save();

    $red = AttributeValues::query()->create([
        'attribute_id' => $attribute->getKey(),
        'value' => ['key' => 'red'],
    ]);

    $blue = AttributeValues::query()->create([
        'attribute_id' => $attribute->getKey(),
        'value' => ['key' => 'blue'],
    ]);

    $products = Product::factory()->withoutAttributeValues()->count(8)->create();

    $products->random(4)->each(function (Product $product) use ($red, $blue): void {
        $product->attributeValues()->attach(fake()->randomElement([$red->getKey(), $blue->getKey()]));
    });

    expect(Product::query()->whereHas('attributeValues')->count())->toBe(4);
});

it('creates translations from explicit attributes merged with defaults', function (): void {
    $product = Product::factory()->withTranslationAttributes([
        'en' => [
            'title' => 'English title',
            'slug' => 'english-title',
            'permalink' => 'https://example.test/p/en',
        ],
        'de' => [
            'title' => 'Deutscher Titel',
            'slug' => 'deutscher-titel',
            'permalink' => 'https://example.test/p/de',
        ],
    ])->create();

    expect($product->translations)->toHaveCount(2);

    $en = $product->translations->firstWhere('locale', 'en');
    expect($en?->title)->toBe('English title');
    expect($en?->slug)->toBe('english-title');

    $de = $product->translations->firstWhere('locale', 'de');
    expect($de?->title)->toBe('Deutscher Titel');
    expect($de?->slug)->toBe('deutscher-titel');
});
