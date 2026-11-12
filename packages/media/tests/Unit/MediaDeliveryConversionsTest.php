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
        'media.delivery.skip_mime_types' => ['image/gif'],
        'media.delivery.profiles' => [
            'web' => [
                'enabled' => true,
                'mode' => 'responsive',
                'formats' => ['avif', 'webp', 'original'],
                'upscale' => false,
                'min_responsive_width' => 480,
                'fallback_to_original' => true,
                'svg' => 'original',
            ],
            'mail' => [
                'enabled' => true,
                'mode' => 'single',
                'formats' => ['jpg', 'png'],
                'upscale' => false,
                'default_width' => 600,
                'fallback_to_original' => true,
                'svg' => 'png',
            ],
            'customer_api' => [
                'enabled' => false,
                'mode' => 'single',
                'formats' => ['jpg', 'png'],
                'upscale' => false,
                'default_width' => 1024,
                'fallback_to_original' => false,
                'svg' => 'skip',
                'skip_static' => true,
                'watermark' => [
                    'enabled' => false,
                    'path' => null,
                ],
            ],
        ],
    ]);
});

it('builds web conversion names from widths and formats', function (): void {
    expect(MediaDeliveryConversions::conversionName('web', 960, 'webp'))->toBe('web-960-webp');

    expect(MediaDeliveryConversions::responsiveWidths())->toContain(480, 768, 960, 1024, 1440)
        ->and(MediaDeliveryConversions::responsiveWidths())->not->toContain(150, 300);
});

it('uses profile widths when configured', function (): void {
    config(['media.delivery.profiles.web.widths' => [640, 1280]]);

    expect(MediaDeliveryConversions::profileWidths('web'))->toBe([640, 1280])
        ->and(MediaDeliveryConversions::responsiveWidths('web'))->toContain(640, 768, 1024, 1280);
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

it('skips gif from delivery eligibility', function (): void {
    $gif = new class extends Media
    {
        public $mime_type = 'image/gif';

        public $file_name = 'anim.gif';
    };

    expect(MediaDeliveryConversions::isRaster($gif))->toBeTrue()
        ->and(MediaDeliveryConversions::isDeliveryEligible($gif))->toBeFalse()
        ->and(MediaDeliveryConversions::shouldApplyProfile($gif, 'web'))->toBeFalse();
});

it('does not allow upscale when original width is known', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->mime_type = 'image/jpeg';
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 900, 'height' => 200]);
    $media->shouldReceive('getCustomProperty')->with('static_path')->andReturn(null);
    $media->shouldReceive('getPath')->andReturn('');

    expect(MediaDeliveryConversions::canProduceWidth($media, 768))->toBeTrue()
        ->and(MediaDeliveryConversions::canProduceWidth($media, 960))->toBeFalse()
        ->and(MediaDeliveryConversions::applicableResponsiveWidths($media))->toBe([480, 768]);
});

it('uses same-size delivery for tiny originals under min_responsive_width', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->mime_type = 'image/jpeg';
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 240, 'height' => 160]);
    $media->shouldReceive('getCustomProperty')->with('static_path')->andReturn(null);
    $media->shouldReceive('getPath')->andReturn('');

    expect(MediaDeliveryConversions::needsSameSizeDelivery($media))->toBeTrue()
        ->and(MediaDeliveryConversions::applicableResponsiveWidths($media))->toBe([240])
        ->and(MediaDeliveryConversions::conversionName('web', 240, 'avif'))->toBe('web-240-avif');
});

it('does not guess a ladder when dimensions are unknown', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->mime_type = 'image/jpeg';
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(null);
    $media->shouldReceive('getPath')->andReturn('');

    expect(MediaDeliveryConversions::canProduceWidth($media, 480))->toBeFalse()
        ->and(MediaDeliveryConversions::applicableResponsiveWidths($media))->toBe([])
        ->and(MediaDeliveryConversions::needsSameSizeDelivery($media))->toBeFalse();
});

it('filters conversion names by profile for regenerate', function (): void {
    config(['media.delivery.profiles.customer_api.enabled' => true]);

    $mailOnly = MediaDeliveryConversions::allConversionNames(['mail']);

    expect($mailOnly)->toContain('mail', 'mail-png')
        ->and($mailOnly)->not->toContain('web-480-avif')
        ->and($mailOnly)->not->toContain('customer_api');
});

it('exposes custom enabled profiles from config', function (): void {
    config([
        'media.delivery.profiles.og' => [
            'enabled' => true,
            'mode' => 'single',
            'formats' => ['jpg'],
            'default_width' => 1200,
            'fallback_to_original' => false,
            'svg' => 'skip',
        ],
    ]);

    expect(MediaDeliveryConversions::enabledProfileNames())->toContain('web', 'mail', 'og')
        ->and(MediaDeliveryConversions::profileMode('og'))->toBe('single')
        ->and(MediaDeliveryConversions::singleConversionName('og'))->toBe('og')
        ->and(MediaDeliveryConversions::allConversionNames(['og']))->toContain('og');
});
