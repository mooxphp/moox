<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Moox\Support\Tests\Fixtures\Product;

uses(RefreshDatabase::class);

it('implements the translatable contract', function (): void {
    expect(new Product)
        ->toBeInstanceOf(
            Astrotomic\Translatable\Contracts\Translatable::class
        );
});

it('uses the translatable trait', function (): void {
    expect(class_uses_recursive(Product::class))
        ->toContain(Astrotomic\Translatable\Translatable::class);
});

it('defines the translated attributes', function (): void {
    expect((new Product)->translatedAttributes)->toBe([
        'title',
        'slug',
        'description',
    ]);
});

it('stores and retrieves all supported locale variants', function (string $locale): void {
    $product = Product::create();

    $product->translateOrNew($locale)->title = "Title {$locale}";
    $product->translateOrNew($locale)->slug = "title-{$locale}";

    $product->save();

    $translation = $product->fresh()->translate($locale);

    expect($translation)->not->toBeNull()
        ->and($translation->locale)->toBe($locale)
        ->and($translation->title)->toBe("Title {$locale}")
        ->and($translation->slug)->toBe("title-{$locale}");
})->with([
    'German Germany' => 'de_DE',
    'English USA' => 'en_US',
    'English Great Britain' => 'en_GB',
    'Polish Poland' => 'pl_PL',
]);

it('stores and retrieves all base locales', function (string $locale): void {
    $product = Product::create();

    $product->translateOrNew($locale)->title = "Title {$locale}";
    $product->translateOrNew($locale)->slug = "title-{$locale}";

    $product->save();

    $translation = $product->fresh()->translate($locale);

    expect($translation)->not->toBeNull()
        ->and($translation->locale)->toBe($locale)
        ->and($translation->title)->toBe("Title {$locale}")
        ->and($translation->slug)->toBe("title-{$locale}");
})->with([
    'German' => 'de',
    'English' => 'en',
    'Polish' => 'pl',
]);

it('stores different translations independently', function (): void {
    $product = Product::create();

    foreach ([
        'de_DE',
        'en_US',
        'en_GB',
        'pl_PL',
        'de',
        'en',
        'pl',
    ] as $locale) {
        $translation = $product->translateOrNew($locale);
        $translation->title = "Title {$locale}";
        $translation->slug = "title-{$locale}";
    }

    $product->save();

    $product = $product->fresh();

    foreach ([
        'de_DE',
        'en_US',
        'en_GB',
        'pl_PL',
        'de',
        'en',
        'pl',
    ] as $locale) {
        expect($product->translate($locale)->title)
            ->toBe("Title {$locale}");
            
    }

    expect($product->translations)->toHaveCount(7);
});

it('can translate natively', function (): void {
    $locale_de = 'de_DE';
    $locale_en = 'en_US';
    $locale_gb = 'en_GB';
    $locale_pl = 'pl_PL';
    $locale_de = 'de';
    $locale_en = 'en';
    $locale_pl = 'pl';

    App::setLocale($locale_de);
    $product = Product::create();

    $product->title = 'Title';
    $product->slug = 'title';
    $product->description = 'Description';
    $product->translateOrNew($locale_en)->title = 'Title English';
    $product->translateOrNew($locale_en)->slug = 'title-english';
    $product->translateOrNew($locale_en)->description = 'Description English';
    $product->translateOrNew($locale_gb)->title = 'Title Great Britain';
    $product->translateOrNew($locale_gb)->slug = 'title-great-britain';
    $product->translateOrNew($locale_gb)->description = 'Description Great Britain';
    $product->translateOrNew($locale_pl)->title = 'Title Polish';
    $product->translateOrNew($locale_pl)->slug = 'title-polish';
    $product->translateOrNew($locale_pl)->description = 'Description Polish';

    $product->save();

    expect($product->title)->toBe('Title');
    expect($product->slug)->toBe('title');
    expect($product->description)->toBe('Description');

    App::setLocale($locale_en);
    expect($product->title)->toBe('Title English');
    expect($product->slug)->toBe('title-english');
    expect($product->description)->toBe('Description English');

    App::setLocale($locale_gb);
    expect($product->title)->toBe('Title Great Britain');
    expect($product->slug)->toBe('title-great-britain');
    expect($product->description)->toBe('Description Great Britain');

    App::setLocale($locale_pl);
    expect($product->title)->toBe('Title Polish');
    expect($product->slug)->toBe('title-polish');
    expect($product->description)->toBe('Description Polish');
});