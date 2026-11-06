<?php

declare(strict_types=1);

use Moox\Media\Support\MediaDeliveryConversions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'media.delivery.sizes' => [
            'thumbnail' => ['width' => 150, 'height' => 150, 'fit' => 'contain'],
            'medium' => ['width' => 300],
            'medium_large' => ['width' => 768],
            'large' => ['width' => 1024],
        ],
        'media.delivery.extra_widths' => [480, 960, 1440],
        'media.delivery.profiles.web.formats' => ['avif', 'webp', 'original'],
        'media.delivery.profiles.web.min_responsive_width' => 150,
    ]);
});

it('builds web conversion names from widths and formats', function (): void {
    expect(MediaDeliveryConversions::conversionName('web', 960, 'webp'))->toBe('web-960-webp');

    expect(MediaDeliveryConversions::responsiveWidths())->toContain(150, 300, 480, 768, 960, 1024, 1440);
});

it('only supports raster and svg mime types', function (): void {
    $jpeg = new class extends Media
    {
        public $mime_type = 'image/jpeg';
    };

    $svg = new class extends Media
    {
        public $mime_type = 'image/svg+xml';
    };

    $pdf = new class extends Media
    {
        public $mime_type = 'application/pdf';
    };

    expect(MediaDeliveryConversions::supports($jpeg))->toBeTrue()
        ->and(MediaDeliveryConversions::isRaster($jpeg))->toBeTrue()
        ->and(MediaDeliveryConversions::isSvg($svg))->toBeTrue()
        ->and(MediaDeliveryConversions::supports($pdf))->toBeFalse();
});

it('does not allow upscale when original width is known', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 400, 'height' => 200]);
    $media->shouldReceive('getPath')->andReturn('');

    expect(MediaDeliveryConversions::canProduceWidth($media, 300))->toBeTrue()
        ->and(MediaDeliveryConversions::canProduceWidth($media, 480))->toBeFalse()
        ->and(MediaDeliveryConversions::applicableResponsiveWidths($media))->toBe([150, 300]);
});

it('skips responsive widths for tiny originals under 150px', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 64, 'height' => 64]);
    $media->shouldReceive('getPath')->andReturn('');

    expect(MediaDeliveryConversions::applicableResponsiveWidths($media))->toBe([]);
});
