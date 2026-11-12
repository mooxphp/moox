<?php

declare(strict_types=1);

use Moox\Media\Models\Media;
use Moox\Media\Support\MediaDeliveryConversions;
use Moox\Media\Support\MediaPictureBuilder;
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
        'media.delivery.profiles' => [
            'web' => [
                'enabled' => true,
                'mode' => 'responsive',
                'formats' => ['avif', 'webp', 'original'],
                'upscale' => false,
                'min_responsive_width' => 150,
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
            ],
        ],
    ]);
});

it('builds picture sources and img srcset from generated web conversions', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->file_name = 'hero.jpg';
    $media->mime_type = 'image/jpeg';
    $media->shouldReceive('loadMissing')->andReturnSelf();
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 2000, 'height' => 1200]);
    $media->shouldReceive('hasGeneratedConversion')->andReturnUsing(
        fn (string $name): bool => str_starts_with($name, 'web-'),
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
        ->and($picture['mode'])->toBe('picture')
        ->and($picture['sizes'])->toBe('(max-width: 960px) 100vw, 960px')
        ->and($picture['sources'])->toHaveCount(2)
        ->and($picture['sources'][0]['type'])->toBe('image/avif')
        ->and($picture['sources'][0]['srcset'])->toContain('480w')
        ->and($picture['sources'][0]['srcset'])->toContain('1440w')
        ->and($picture['sources'][1]['type'])->toBe('image/webp')
        ->and($picture['img']['srcset'])->toContain('web-480-jpg')
        ->and($picture['img']['loading'])->toBe('lazy');

    $html = app(MediaPictureBuilder::class)->toHtml($media, sizes: '100vw', alt: 'Hero');

    expect($html)->toContain('<picture>')
        ->and($html)->toContain('type="image/avif"')
        ->and($html)->toContain('type="image/webp"')
        ->and($html)->toContain('loading="lazy"')
        ->and($html)->toContain('decoding="async"')
        ->and($html)->toContain('alt="Hero"');
});

it('renders svg as simple img for web without srcset', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->file_name = 'logo.svg';
    $media->mime_type = 'image/svg+xml';
    $media->shouldReceive('loadMissing')->andReturnSelf();
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(null);
    $media->shouldReceive('getUrl')->with('')->andReturn('https://example.test/media/logo.svg');
    $media->shouldReceive('getUrl')->withNoArgs()->andReturn('https://example.test/media/logo.svg');

    $picture = app(MediaPictureBuilder::class)->toArray($media, alt: 'Logo', profile: MediaDeliveryConversions::PROFILE_WEB);

    expect($picture['mode'])->toBe('svg')
        ->and($picture['sources'])->toBe([])
        ->and($picture['img']['src'])->toBe('https://example.test/media/logo.svg')
        ->and($picture['img']['srcset'])->toBeNull();
});

it('uses mail conversion url for mail profile', function (): void {
    $media = Mockery::mock(Media::class)->makePartial();
    $media->file_name = 'hero.jpg';
    $media->mime_type = 'image/jpeg';
    $media->shouldReceive('loadMissing')->andReturnSelf();
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 800, 'height' => 600]);
    $media->shouldReceive('hasGeneratedConversion')->andReturnUsing(
        fn (string $name): bool => $name === MediaDeliveryConversions::MAIL_RASTER,
    );
    $media->shouldReceive('getUrl')->andReturnUsing(
        fn (string $conversion = '') => $conversion === ''
            ? 'https://example.test/media/hero.jpg'
            : 'https://example.test/media/conversions/'.$conversion.'.jpg',
    );

    $picture = app(MediaPictureBuilder::class)->toArray(
        $media,
        alt: 'Hero',
        profile: MediaDeliveryConversions::PROFILE_MAIL,
    );

    expect($picture['mode'])->toBe('img')
        ->and($picture['img']['src'])->toContain('mail')
        ->and($picture['img']['srcset'])->toBeNull()
        ->and($picture['sources'])->toBe([]);
});

it('serves a custom single profile without falling back to original', function (): void {
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

    $media = Mockery::mock(Media::class)->makePartial();
    $media->file_name = 'hero.jpg';
    $media->mime_type = 'image/jpeg';
    $media->shouldReceive('loadMissing')->andReturnSelf();
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 1600, 'height' => 900]);
    $media->shouldReceive('hasGeneratedConversion')->andReturn(false);
    $media->shouldReceive('getUrl')->andReturn('https://example.test/media/hero.jpg');

    $picture = app(MediaPictureBuilder::class)->toArray($media, alt: 'OG', profile: 'og');

    expect($picture['profile'])->toBe('og')
        ->and($picture['mode'])->toBe('img')
        ->and($picture['img']['src'])->toBeNull();
});
