<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Moox\Media\Models\Media;

final class StaticMedia
{
    public const PROPERTY = 'static_path';

    public static function find(string $staticPath): ?Media
    {
        $normalized = self::normalize($staticPath);

        return Media::query()
            ->where('custom_properties->'.self::PROPERTY, $normalized)
            ->first();
    }

    public static function normalize(string $staticPath): string
    {
        return ltrim(str_replace('\\', '/', $staticPath), '/');
    }

    public static function remember(Media $media, string $staticPath): void
    {
        $media->setCustomProperty(self::PROPERTY, self::normalize($staticPath));
        $media->save();
    }
}
