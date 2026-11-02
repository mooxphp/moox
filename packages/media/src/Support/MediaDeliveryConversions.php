<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaDeliveryConversions
{
    public static function register(HasMedia $model, ?Media $media = null): void
    {
        if ($media !== null && ! self::supports($media)) {
            return;
        }

        foreach (self::widths() as $width) {
            foreach (self::formats() as $format) {
                /** @var Conversion $conversion */
                $conversion = $model->addMediaConversion(self::conversionName($width, $format));
                $conversion->width($width);
                $conversion->format($format);
                $conversion->queued();

                $quality = self::qualityFor($format);
                if ($quality !== null) {
                    $conversion->quality($quality);
                }
            }
        }
    }

    public static function supports(Media $media): bool
    {
        $mime = strtolower((string) ($media->mime_type ?? ''));

        return in_array($mime, self::rasterMimeTypes(), true);
    }

    public static function conversionName(int $width, string $format): string
    {
        return "delivery-{$width}-{$format}";
    }

    /**
     * @return list<string>
     */
    public static function allConversionNames(): array
    {
        $names = [];

        foreach (self::widths() as $width) {
            foreach (self::formats() as $format) {
                $names[] = self::conversionName($width, $format);
            }
        }

        return $names;
    }

    /**
     * @return list<int>
     */
    public static function widths(): array
    {
        $widths = config('media.delivery.widths', [480, 960, 1440]);

        if (! is_array($widths)) {
            return [480, 960, 1440];
        }

        $normalized = [];
        foreach ($widths as $width) {
            if (is_int($width) || is_string($width)) {
                $normalized[] = (int) $width;
            }
        }

        return $normalized !== [] ? $normalized : [480, 960, 1440];
    }

    /**
     * @return list<string>
     */
    public static function formats(): array
    {
        $formats = config('media.delivery.formats', ['avif', 'webp', 'jpg']);

        if (! is_array($formats)) {
            return ['avif', 'webp', 'jpg'];
        }

        $normalized = [];
        foreach ($formats as $format) {
            if (is_string($format) && $format !== '') {
                $normalized[] = $format;
            }
        }

        return $normalized !== [] ? $normalized : ['avif', 'webp', 'jpg'];
    }

    /**
     * @return list<string>
     */
    public static function rasterMimeTypes(): array
    {
        $types = config('media.delivery.raster_mime_types', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/avif',
        ]);

        if (! is_array($types)) {
            return [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/avif',
            ];
        }

        $normalized = [];
        foreach ($types as $type) {
            if (is_string($type) && $type !== '') {
                $normalized[] = $type;
            }
        }

        return $normalized;
    }

    public static function qualityFor(string $format): ?int
    {
        $qualities = config('media.delivery.quality', []);

        if (! is_array($qualities) || ! array_key_exists($format, $qualities)) {
            return null;
        }

        $quality = $qualities[$format];

        if (! is_int($quality) && ! is_string($quality)) {
            return null;
        }

        return (int) $quality;
    }
}
