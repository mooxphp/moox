<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Spatie\Image\Drivers\Vips\VipsDriver;

final class ImageDriverResolver
{
    /**
     * Prefer Vips, then Imagick, then GD when media.image_driver is "auto".
     */
    public static function apply(): void
    {
        config(['media-library.image_driver' => self::resolve()]);
    }

    public static function resolve(): string
    {
        $preferred = config('media.image_driver', 'auto');

        if (! is_string($preferred) || $preferred === '' || $preferred === 'auto') {
            return self::detect();
        }

        return self::normalize($preferred);
    }

    public static function detect(): string
    {
        if (self::vipsAvailable()) {
            return 'vips';
        }

        if (extension_loaded('imagick')) {
            return 'imagick';
        }

        return 'gd';
    }

    /**
     * @return array{driver: string, preferred: string, vips: bool, imagick: bool, gd: bool, optimizers: array<string, bool>}
     */
    public static function status(): array
    {
        $preferred = config('media.image_driver', 'auto');

        return [
            'driver' => self::resolve(),
            'preferred' => is_string($preferred) && $preferred !== '' ? $preferred : 'auto',
            'vips' => self::vipsAvailable(),
            'imagick' => extension_loaded('imagick'),
            'gd' => extension_loaded('gd'),
            'optimizers' => [
                'jpegoptim' => self::binaryExists('jpegoptim'),
                'optipng' => self::binaryExists('optipng'),
                'pngquant' => self::binaryExists('pngquant'),
                'cwebp' => self::binaryExists('cwebp'),
                'avifenc' => self::binaryExists('avifenc'),
            ],
        ];
    }

    private static function vipsAvailable(): bool
    {
        if (! extension_loaded('vips')) {
            return false;
        }

        return class_exists(VipsDriver::class);
    }

    private static function normalize(string $driver): string
    {
        $driver = strtolower(trim($driver));

        return match ($driver) {
            'vips', 'imagick', 'gd' => $driver,
            default => 'gd',
        };
    }

    private static function binaryExists(string $binary): bool
    {
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'command -v';

        $result = @shell_exec($which.' '.escapeshellarg($binary).' 2>/dev/null');

        return is_string($result) && trim($result) !== '';
    }
}
