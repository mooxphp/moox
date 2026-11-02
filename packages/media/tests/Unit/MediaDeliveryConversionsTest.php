<?php

declare(strict_types=1);

use Moox\Media\Support\MediaDeliveryConversions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'media.delivery.widths' => [480, 960, 1440],
        'media.delivery.formats' => ['avif', 'webp', 'jpg'],
    ]);
});

it('builds delivery conversion names for each width and format', function (): void {
    expect(MediaDeliveryConversions::conversionName(960, 'webp'))->toBe('delivery-960-webp');

    expect(MediaDeliveryConversions::allConversionNames())->toBe([
        'delivery-480-avif',
        'delivery-480-webp',
        'delivery-480-jpg',
        'delivery-960-avif',
        'delivery-960-webp',
        'delivery-960-jpg',
        'delivery-1440-avif',
        'delivery-1440-webp',
        'delivery-1440-jpg',
    ]);
});

it('only supports configured raster mime types', function (): void {
    $jpeg = new class extends Media
    {
        public $mime_type = 'image/jpeg';
    };

    $svg = new class extends Media
    {
        public $mime_type = 'image/svg+xml';
    };

    expect(MediaDeliveryConversions::supports($jpeg))->toBeTrue()
        ->and(MediaDeliveryConversions::supports($svg))->toBeFalse();
});
