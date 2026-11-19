<?php

declare(strict_types=1);

use Moox\Media\Models\Media;
use Moox\Media\Support\HtmlableMedia;
use Moox\Media\Support\MediaDeliveryConversions;
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
        'media-library.default_loading_attribute_value' => null,
    ]);
});

function mockRenderableMedia(): Media
{
    $media = Mockery::mock(Media::class)->makePartial();
    $media->file_name = 'hero.jpg';
    $media->mime_type = 'image/jpeg';
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 2000, 'height' => 1200]);
    $media->shouldReceive('hasGeneratedConversion')->andReturnUsing(
        function (string $name): bool {
            return str_starts_with($name, 'web-')
                || $name === MediaDeliveryConversions::PROFILE_MAIL
                || $name === 'medium';
        },
    );
    $media->shouldReceive('getUrl')->andReturnUsing(
        function (string $conversion = '') {
            if ($conversion === '') {
                return 'https://example.test/media/hero.jpg';
            }

            return 'https://example.test/media/conversions/'.$conversion.'.bin';
        },
    );

    return $media;
}

it('defaults img() without conversion to web delivery html', function (): void {
    $media = mockRenderableMedia();

    $html = (new HtmlableMedia($media))->alt('Hero')->toHtml();

    expect($html)->toContain('<picture>')
        ->and($html)->toContain('type="image/avif"')
        ->and($html)->toContain('type="image/webp"')
        ->and($html)->toContain('web-480-avif')
        ->and($html)->toContain('alt="Hero"')
        ->and($html)->not->toContain('conversions/medium.bin');
});

it('renders an explicit conversion as a simple admin img', function (): void {
    $media = mockRenderableMedia();

    $html = (new HtmlableMedia($media))
        ->conversion('medium')
        ->alt('Thumb')
        ->lazy()
        ->attributes(['class' => 'rounded'])
        ->toHtml();

    expect($html)->toStartWith('<img ')
        ->and($html)->toContain('src="https://example.test/media/conversions/medium.bin"')
        ->and($html)->toContain('class="rounded"')
        ->and($html)->toContain('loading="lazy"')
        ->and($html)->toContain('alt="Thumb"')
        ->and($html)->not->toContain('<picture>');
});

it('lets profile win over conversion', function (): void {
    $media = mockRenderableMedia();

    $html = (new HtmlableMedia($media))
        ->conversion('medium')
        ->profile('mail')
        ->alt('Mail shot')
        ->toHtml();

    expect($html)->toContain('src="https://example.test/media/conversions/mail.bin"')
        ->and($html)->toContain('alt="Mail shot"')
        ->and($html)->not->toContain('conversions/medium.bin')
        ->and($html)->not->toContain('<picture>');
});
