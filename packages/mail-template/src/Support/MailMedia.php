<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Support;

use Illuminate\Support\Facades\Storage;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Media\Models\Media;

final class MailMedia
{
    public static function isAvailable(): bool
    {
        return class_exists('Moox\Media\Forms\Components\MediaPicker');
    }

    /**
     * @return class-string<MediaPicker>|null
     */
    public static function pickerClass(): ?string
    {
        $class = 'Moox\Media\Forms\Components\MediaPicker';

        return class_exists($class) ? $class : null;
    }

    public static function resolveUrl(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return null;
            }

            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return self::resolveUrl($decoded);
                }
            }

            return self::urlFromPath($trimmed);
        }

        if (! is_array($value)) {
            return null;
        }

        if (! self::isSnapshot($value)) {
            $first = reset($value);

            if (is_array($first)) {
                return self::resolveUrl($first);
            }
        }

        $id = $value['id'] ?? null;

        if (is_numeric($id) && class_exists('Moox\Media\Models\Media')) {
            $media = Media::query()->find($id);

            if ($media !== null) {
                $url = $media->getUrl();

                if ($url !== '') {
                    return $url;
                }
            }
        }

        foreach (['url', 'original_url'] as $key) {
            $direct = $value[$key] ?? null;

            if (is_string($direct) && $direct !== '') {
                return self::urlFromPath($direct);
            }
        }

        $fileName = $value['file_name'] ?? null;

        if (is_string($fileName) && $fileName !== '') {
            return self::urlFromPath($fileName);
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private static function isSnapshot(array $value): bool
    {
        return array_key_exists('id', $value)
            || array_key_exists('file_name', $value)
            || array_key_exists('url', $value)
            || array_key_exists('original_url', $value);
    }

    private static function urlFromPath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
