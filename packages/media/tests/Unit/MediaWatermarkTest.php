<?php

declare(strict_types=1);

use Moox\Media\Support\MediaWatermark;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\Conversions\Conversion;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $path = sys_get_temp_dir().'/moox-media-watermark-test.png';
    if (! is_file($path)) {
        // 1x1 PNG
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        file_put_contents($path, $png);
    }

    config([
        'media.delivery.profiles.customer_api' => [
            'enabled' => true,
            'mode' => 'single',
            'formats' => ['jpg'],
            'watermark' => [
                'enabled' => true,
                'path' => $path,
                'opacity' => 35,
                'width_percent' => 12,
                'padding_percent' => 4,
                'position' => 'bottom_left',
                'fit' => 'contain',
            ],
        ],
        'media.delivery.profiles.web' => [
            'enabled' => true,
            'mode' => 'responsive',
            'formats' => ['webp'],
            'watermark' => [
                'enabled' => false,
                'path' => $path,
            ],
        ],
    ]);
});

it('builds a watermark from an enabled profile', function (): void {
    $watermark = MediaWatermark::fromProfile('customer_api');

    expect($watermark)->toBeInstanceOf(MediaWatermark::class)
        ->and($watermark->opacity)->toBe(35)
        ->and($watermark->widthPercent)->toBe(12)
        ->and($watermark->paddingPercent)->toBe(4)
        ->and($watermark->position)->toBe(AlignPosition::BottomLeft)
        ->and($watermark->fit)->toBe(Fit::Contain)
        ->and(is_file($watermark->path))->toBeTrue();
});

it('returns null when watermark is disabled for the profile', function (): void {
    expect(MediaWatermark::fromProfile('web'))->toBeNull();
});

it('applies watermark manipulations onto a conversion', function (): void {
    $watermark = MediaWatermark::fromProfile('customer_api');
    $conversion = new Conversion('customer_api');

    $watermark->applyToConversion($conversion);

    $argument = $conversion->getManipulations()->getManipulationArgument('watermark');

    expect($argument)->toBeArray()
        ->and($argument[0])->toBe($watermark->path)
        ->and($argument[1])->toBe(AlignPosition::BottomLeft);
});
