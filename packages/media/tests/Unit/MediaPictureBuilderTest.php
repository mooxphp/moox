<?php

declare(strict_types=1);

use Moox\Media\Models\Media;
use Moox\Media\Support\MediaPictureBuilder;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'media.delivery.widths' => [480, 960, 1440],
        'media.delivery.formats' => ['avif', 'webp', 'jpg'],
    ]);
});

it('builds picture sources and img srcset from generated conversions', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->file_name = 'hero.jpg';
    $media->shouldReceive('loadMissing')->andReturnSelf();
    $media->shouldReceive('hasGeneratedConversion')->andReturnUsing(
        fn (string $name): bool => str_starts_with($name, 'delivery-'),
    );
    $media->shouldReceive('getUrl')->andReturnUsing(
        function (string $conversion = '') {
            if ($conversion === '') {
                return 'https://example.test/media/hero.jpg';
            }

            return 'https://example.test/media/conversions/'.$conversion.'.bin';
        },
    );

    $picture = app(MediaPictureBuilder::class)->toArray($media, sizes: '(max-width: 960px) 100vw, 960px', alt: 'Hero');

    expect($picture['alt'])->toBe('Hero')
        ->and($picture['sizes'])->toBe('(max-width: 960px) 100vw, 960px')
        ->and($picture['sources'])->toHaveCount(2)
        ->and($picture['sources'][0]['type'])->toBe('image/avif')
        ->and($picture['sources'][0]['srcset'])->toContain('480w')
        ->and($picture['sources'][0]['srcset'])->toContain('1440w')
        ->and($picture['sources'][1]['type'])->toBe('image/webp')
        ->and($picture['img']['src'])->toContain('delivery-960-jpg')
        ->and($picture['img']['srcset'])->toContain('delivery-480-jpg')
        ->and($picture['img']['loading'])->toBe('lazy');

    $html = app(MediaPictureBuilder::class)->toHtml($media, sizes: '100vw', alt: 'Hero');

    expect($html)->toContain('<picture>')
        ->and($html)->toContain('type="image/avif"')
        ->and($html)->toContain('type="image/webp"')
        ->and($html)->toContain('loading="lazy"')
        ->and($html)->toContain('decoding="async"')
        ->and($html)->toContain('alt="Hero"');
});

it('omits missing conversions from srcset', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->file_name = 'hero.jpg';
    $media->shouldReceive('loadMissing')->andReturnSelf();
    $media->shouldReceive('hasGeneratedConversion')->andReturnUsing(
        fn (string $name): bool => $name === 'delivery-960-jpg',
    );
    $media->shouldReceive('getUrl')->andReturnUsing(
        fn (string $conversion = '') => $conversion === ''
            ? 'https://example.test/media/hero.jpg'
            : 'https://example.test/media/conversions/'.$conversion.'.jpg',
    );

    $picture = app(MediaPictureBuilder::class)->toArray($media, alt: 'Only jpg');

    expect($picture['sources'])->toBe([])
        ->and($picture['img']['srcset'])->toBe('https://example.test/media/conversions/delivery-960-jpg.jpg 960w')
        ->and($picture['img']['src'])->toContain('delivery-960-jpg');
});
