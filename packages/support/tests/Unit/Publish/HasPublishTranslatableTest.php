<?php

declare(strict_types=1);


use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\Support\Tests\Fixtures\Product;

uses(RefreshDatabase::class);

it('publishes the default translation when publishing a product without published translations', function (): void {
    $defaultLocale = config('app.locale');

    $product = Product::create();

    $translation = $product->translateOrNew($defaultLocale);
    $translation->title = 'Product';
    $product->save();

    $product->publish();

    expect($product->fresh()->isPublished())->toBeTrue()
        ->and($product->translate($defaultLocale)->fresh()->isPublished())->toBeTrue();
});

it('publishes a product when at least one translation is published', function (): void {
    $product = Product::create();

    $product->translateOrNew('de')->title = 'Produkt';
    $product->translateOrNew('en')->title = 'Product';
    $product->save();

    $product->translate('de')->publish();
    $product->publish();

    expect($product->fresh()->isPublished())->toBeTrue()
        ->and($product->translate('de')->isPublished())->toBeTrue()
        ->and($product->translate('en')->isPublished())->toBeFalse();
});

it('publishes translations independently from the product', function (): void {
    $product = Product::create();

    $product->translateOrNew('de')->title = 'Produkt';
    $product->translateOrNew('en')->title = 'Product';
    $product->save();

    $product->translate('de')->publish();

    expect($product->fresh()->isPublished())->toBeFalse()
        ->and($product->translate('de')->isPublished())->toBeTrue()
        ->and($product->translate('en')->isPublished())->toBeFalse();
});

it('can publish and unpublish individual translations while the product is unpublished', function (): void {
    $product = Product::create();

    $product->translateOrNew('de')->title = 'Produkt';
    $product->translateOrNew('en')->title = 'Product';
    $product->save();

    $product->translate('de')->publish();
    $product->translate('en')->publish();

    $product->translate('en')->unpublish();

    expect($product->translate('de')->fresh()->isPublished())->toBeTrue()
        ->and($product->translate('en')->fresh()->isPublished())->toBeFalse()
        ->and($product->translate('en')->fresh()->isUnpublished())->toBeTrue();
});

it('returns published and unpublished translations through scopes', function (): void {
    $product = Product::create();

    $product->translateOrNew('de')->title = 'Produkt';
    $product->translateOrNew('en')->title = 'Product';
    $product->save();

    $product->translate('de')->publish();
    $product->translate('en')->publish();
    $product->translate('en')->unpublish();

    expect($product->translations()->published()->count())->toBe(1)
        ->and($product->translations()->unpublished()->count())->toBe(1)
        ->and($product->translations()->notPublished()->count())->toBe(1);
});

it('allows unpublishing the last published translation independently of the product', function (): void {
    $product = Product::create();

    $product->translateOrNew('de')->title = 'Produkt';
    $product->save();

    $product->translate('de')->publish();
    $product->publish();

    $product->translate('de')->unpublish();

    expect($product->fresh()->isPublished())->toBeTrue()
        ->and($product->translate('de')->fresh()->isPublished())->toBeFalse()
        ->and($product->translate('de')->fresh()->isUnpublished())->toBeTrue();
});

it('allows unpublishing a translation when another translation remains published', function (): void {
    $product = Product::create();

    $product->translateOrNew('de')->title = 'Produkt';
    $product->translateOrNew('en')->title = 'Product';
    $product->save();

    $product->translate('de')->publish();
    $product->translate('en')->publish();
    $product->publish();

    $product->translate('en')->unpublish();

    expect($product->fresh()->isPublished())->toBeTrue()
        ->and($product->translate('de')->fresh()->isPublished())->toBeTrue()
        ->and($product->translate('en')->fresh()->isPublished())->toBeFalse();
});

it('publishes the default translation when no translation is published', function (): void {
    $defaultLocale = config('app.locale');

    $product = Product::create();

    $product->translateOrNew('de')->title = 'Produkt';

    $defaultTranslation = $product->translateOrNew($defaultLocale);
    $defaultTranslation->title = 'Product';

    $product->save();
    $product->publish();

    expect($product->translations()->published()->count())->toBe(1)
        ->and($product->translate($defaultLocale)->fresh()->isPublished())->toBeTrue();
});
