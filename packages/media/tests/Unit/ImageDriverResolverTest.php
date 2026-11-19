<?php

declare(strict_types=1);

use Moox\Media\Support\ImageDriverResolver;
use Tests\TestCase;

uses(TestCase::class);

it('honours an explicit media.image_driver config value', function (): void {
    config(['media.image_driver' => 'gd']);

    expect(ImageDriverResolver::resolve())->toBe('gd');
});

it('auto-detects a local driver when media.image_driver is auto', function (): void {
    config(['media.image_driver' => 'auto']);

    $driver = ImageDriverResolver::detect();

    expect($driver)->toBeIn(['vips', 'imagick', 'gd']);
    expect(ImageDriverResolver::resolve())->toBe($driver);
});

it('returns a status payload with optimizer keys', function (): void {
    $status = ImageDriverResolver::status();

    expect($status)->toHaveKeys(['driver', 'preferred', 'vips', 'imagick', 'gd', 'optimizers'])
        ->and($status['optimizers'])->toHaveKeys(['jpegoptim', 'cwebp', 'avifenc']);
});
