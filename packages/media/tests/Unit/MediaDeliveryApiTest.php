<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Moox\Media\Http\Resources\MediaItemResource;
use Moox\Media\Models\Media;
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
        'media.delivery.skip_mime_types' => ['image/gif'],
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
                'enabled' => true,
                'mode' => 'single',
                'formats' => ['jpg', 'png'],
                'upscale' => false,
                'default_width' => 1024,
                'fallback_to_original' => false,
                'svg' => 'skip',
                'skip_static' => true,
            ],
        ],
    ]);
});

/**
 * @param  list<string>  $generated
 */
function deliveryApiMedia(array $generated = []): Media
{
    $media = Mockery::mock(Media::class)->makePartial();
    $media->file_name = 'hero.jpg';
    $media->mime_type = 'image/jpeg';
    $media->created_at = null;
    $media->updated_at = null;
    $media->setRelation('translations', collect());
    $media->setRelation('collection', null);
    $media->shouldReceive('getKey')->andReturn(42);
    $media->shouldReceive('relationLoaded')->with('collection')->andReturn(true);
    $media->shouldReceive('getCustomProperty')->with('dimensions')->andReturn(['width' => 2000, 'height' => 1200]);
    $media->shouldReceive('getCustomProperty')->with('static_path')->andReturn(null);
    $media->shouldReceive('hasGeneratedConversion')->andReturnUsing(
        fn (string $name): bool => in_array($name, $generated, true),
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

it('includes picture and delivery profiles in the media item resource', function (): void {
    $media = deliveryApiMedia([
        'web-480-avif',
        'web-480-webp',
        'web-960-avif',
        'web-960-webp',
        'mail',
        'thumbnail',
        'preview',
    ]);

    $payload = MediaItemResource::make($media)->resolve(Request::create('/api/media/42', 'GET'));

    expect($payload)->toHaveKeys(['picture', 'delivery', 'url', 'variants'])
        ->and($payload['picture']['profile'])->toBe(MediaDeliveryConversions::PROFILE_WEB)
        ->and($payload['delivery'])->toHaveKeys(['web', 'mail', 'customer_api'])
        ->and($payload['delivery']['web']['mode'])->toBe('picture')
        ->and($payload['delivery']['mail'])->toHaveKey('url')
        ->and($payload['delivery']['mail']['url'])->toBe('https://example.test/media/conversions/mail.bin');
});

it('keeps customer_api delivery url null until the conversion exists', function (): void {
    $without = deliveryApiMedia(['mail', 'thumbnail']);

    $payloadWithout = MediaItemResource::make($without)->resolve(Request::create('/api/media/42', 'GET'));

    expect($payloadWithout['delivery']['customer_api']['url'])->toBeNull()
        ->and($payloadWithout['url'])->toBe('https://example.test/media/hero.jpg');

    $with = deliveryApiMedia(['customer_api', 'mail']);

    $payloadWith = MediaItemResource::make($with)->resolve(Request::create('/api/media/42', 'GET'));

    expect($payloadWith['delivery']['customer_api']['url'])
        ->toBe('https://example.test/media/conversions/customer_api.bin');
});
