<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaDeliveryConversions
{
    public const PROFILE_WEB = 'web';

    public const PROFILE_MAIL = 'mail';

    public const PROFILE_CUSTOMER_API = 'customer_api';

    public const MAIL_PNG = 'mail-png';

    public const MAIL_RASTER = 'mail';

    public const CUSTOMER_API = 'customer_api';

    public const MODE_RESPONSIVE = 'responsive';

    public const MODE_SINGLE = 'single';

    public const MIN_RESPONSIVE_WIDTH = 150;

    public static function register(HasMedia $model, ?Media $media = null): void
    {
        if ($media !== null && self::isSvg($media)) {
            self::registerSvgConversions($model, $media);

            return;
        }

        if ($media !== null && ! self::isRaster($media)) {
            return;
        }

        self::registerRasterAdminSizes($model, $media);

        if ($media !== null && ! self::isDeliveryEligible($media)) {
            return;
        }

        foreach (self::enabledProfileNames() as $profile) {
            if (! self::shouldApplyProfile($media, $profile)) {
                continue;
            }

            if (self::profileMode($profile) === self::MODE_RESPONSIVE) {
                self::registerResponsiveProfile($model, $media, $profile);

                continue;
            }

            if ($media !== null && ! self::isDeliveryEligible($media)) {
                continue;
            }

            self::registerSingleProfile($model, $media, $profile);
        }
    }

    public static function isSvg(Media $media): bool
    {
        return strtolower((string) ($media->mime_type ?? '')) === 'image/svg+xml'
            || str_ends_with(strtolower((string) ($media->file_name ?? '')), '.svg');
    }

    public static function isRaster(Media $media): bool
    {
        $mime = strtolower((string) ($media->mime_type ?? ''));

        return in_array($mime, self::rasterMimeTypes(), true);
    }

    /**
     * Whether delivery profiles (responsive/single/watermark) should run for this file.
     * Admin WP sizes still apply for rasters even when delivery is skipped (e.g. GIF).
     */
    public static function isDeliveryEligible(Media $media): bool
    {
        if (self::isSvg($media)) {
            return true;
        }

        if (! self::isRaster($media)) {
            return false;
        }

        $mime = strtolower((string) ($media->mime_type ?? ''));

        return ! in_array($mime, self::skipMimeTypes(), true);
    }

    public static function supports(Media $media): bool
    {
        return self::isRaster($media) || self::isSvg($media);
    }

    public static function originalWidth(Media $media): ?int
    {
        $dimensions = $media->getCustomProperty('dimensions');
        if (is_array($dimensions) && isset($dimensions['width'])) {
            return (int) $dimensions['width'];
        }

        try {
            $path = $media->getPath();
            if (! is_string($path) || $path === '' || ! is_file($path)) {
                return null;
            }

            $size = @getimagesize($path);
            if ($size === false) {
                return null;
            }

            return (int) $size[0];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, array{width: int, height?: int|null, fit?: string, crop?: bool}>
     */
    public static function sizes(): array
    {
        $sizes = config('media.delivery.sizes', [
            'thumbnail' => ['width' => 150, 'height' => 150, 'fit' => 'contain'],
            'medium' => ['width' => 300],
            'medium_large' => ['width' => 768],
            'large' => ['width' => 1024],
        ]);

        return is_array($sizes) ? $sizes : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function profiles(): array
    {
        $profiles = config('media.delivery.profiles', []);

        return is_array($profiles) ? $profiles : [];
    }

    /**
     * @return list<string>
     */
    public static function enabledProfileNames(): array
    {
        $names = [];

        foreach (self::profiles() as $name => $definition) {
            if (! is_string($name) || $name === '' || ! is_array($definition)) {
                continue;
            }

            if (self::profileEnabled($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public static function profileExists(string $profile): bool
    {
        $definition = self::profiles()[$profile] ?? null;

        return is_array($definition);
    }

    public static function profileEnabled(string $profile): bool
    {
        $definition = self::profiles()[$profile] ?? null;
        if (! is_array($definition)) {
            return false;
        }

        return (bool) ($definition['enabled'] ?? true);
    }

    /**
     * @return array<string, mixed>
     */
    public static function profileConfig(string $profile): array
    {
        $definition = self::profiles()[$profile] ?? null;

        return is_array($definition) ? $definition : [];
    }

    public static function profileMode(string $profile): string
    {
        $definition = self::profileConfig($profile);

        if (isset($definition['mode']) && is_string($definition['mode'])) {
            return $definition['mode'] === self::MODE_SINGLE ? self::MODE_SINGLE : self::MODE_RESPONSIVE;
        }

        if (array_key_exists('responsive', $definition)) {
            return (bool) $definition['responsive'] ? self::MODE_RESPONSIVE : self::MODE_SINGLE;
        }

        return self::MODE_RESPONSIVE;
    }

    public static function profileSvgMode(string $profile): string
    {
        $definition = self::profileConfig($profile);

        if (isset($definition['svg']) && is_string($definition['svg'])) {
            return match ($definition['svg']) {
                'png' => 'png',
                'skip' => 'skip',
                default => 'original',
            };
        }

        if ((bool) ($definition['svg_to_png'] ?? false)) {
            return 'png';
        }

        return 'original';
    }

    public static function profileAllowsUpscale(string $profile): bool
    {
        return (bool) (self::profileConfig($profile)['upscale'] ?? false);
    }

    public static function profileFallbackToOriginal(string $profile): bool
    {
        $definition = self::profileConfig($profile);

        if (array_key_exists('fallback_to_original', $definition)) {
            return (bool) $definition['fallback_to_original'];
        }

        return $profile !== self::PROFILE_CUSTOMER_API;
    }

    public static function singleConversionName(string $profile): string
    {
        return $profile;
    }

    public static function svgConversionName(string $profile): string
    {
        return $profile.'-png';
    }

    /**
     * Configured breakpoint widths for a responsive profile (before no-upscale filter).
     *
     * @return list<int>
     */
    public static function profileWidths(?string $profile = null): array
    {
        $profile ??= self::PROFILE_WEB;
        $definition = self::profileConfig($profile);
        $configured = $definition['widths'] ?? null;

        if (is_array($configured) && $configured !== []) {
            $widths = [];
            foreach ($configured as $width) {
                $widths[] = (int) $width;
            }

            $widths = array_values(array_unique(array_filter($widths, static fn (int $w): bool => $w > 0)));
            sort($widths);

            return $widths;
        }

        return self::defaultExtraWidths();
    }

    /**
     * @return list<int>
     */
    public static function defaultExtraWidths(): array
    {
        $extra = config('media.delivery.extra_widths', [480, 960, 1440]);
        if (! is_array($extra)) {
            return [480, 960, 1440];
        }

        $widths = [];
        foreach ($extra as $width) {
            $widths[] = (int) $width;
        }

        $widths = array_values(array_unique(array_filter($widths, static fn (int $w): bool => $w > 0)));
        sort($widths);

        return $widths;
    }

    /**
     * @return list<int>
     */
    public static function responsiveWidths(?string $profile = null): array
    {
        $profile ??= self::PROFILE_WEB;
        $min = self::minResponsiveWidth($profile);
        $widths = [];

        foreach (self::sizes() as $definition) {
            if (! is_array($definition) || ! isset($definition['width'])) {
                continue;
            }

            $width = (int) $definition['width'];
            if ($width >= $min) {
                $widths[] = $width;
            }
        }

        foreach (self::profileWidths($profile) as $width) {
            if ($width >= $min) {
                $widths[] = $width;
            }
        }

        $widths = array_values(array_unique(array_filter($widths, static fn (int $w): bool => $w > 0 && $w >= $min)));
        sort($widths);

        return $widths;
    }

    public static function minResponsiveWidth(?string $profile = null): int
    {
        $profile ??= self::PROFILE_WEB;
        $configured = self::profileConfig($profile)['min_responsive_width'] ?? null;

        if (is_int($configured) || is_string($configured)) {
            return max(1, (int) $configured);
        }

        return self::MIN_RESPONSIVE_WIDTH;
    }

    public static function canProduceWidth(?Media $media, int $targetWidth, bool $allowUpscale = false): bool
    {
        if ($allowUpscale || $media === null) {
            return true;
        }

        $originalWidth = self::originalWidth($media);
        // Known media without dimensions: do not guess a ladder (avoids accidental upscale).
        if ($originalWidth === null) {
            return false;
        }

        return $originalWidth >= $targetWidth;
    }

    public static function needsSameSizeDelivery(Media $media, ?string $profile = null): bool
    {
        $profile ??= self::PROFILE_WEB;

        if (self::profileMode($profile) !== self::MODE_RESPONSIVE) {
            return false;
        }

        if (! self::isDeliveryEligible($media) || self::isSvg($media)) {
            return false;
        }

        $originalWidth = self::originalWidth($media);
        if ($originalWidth === null) {
            return false;
        }

        return $originalWidth < self::minResponsiveWidth($profile);
    }

    /**
     * Widths that should appear in a responsive/same-size delivery for this media.
     *
     * @return list<int>
     */
    public static function applicableResponsiveWidths(Media $media, ?string $profile = null): array
    {
        $profile ??= self::PROFILE_WEB;

        if (! self::isDeliveryEligible($media)) {
            return [];
        }

        if (self::needsSameSizeDelivery($media, $profile)) {
            $originalWidth = self::originalWidth($media);

            return $originalWidth !== null && $originalWidth > 0 ? [$originalWidth] : [];
        }

        $originalWidth = self::originalWidth($media);
        $min = self::minResponsiveWidth($profile);
        if ($originalWidth !== null && $originalWidth < $min) {
            return [];
        }

        // Unknown dimensions on a concrete media: no ladder.
        if ($originalWidth === null) {
            return [];
        }

        $allowUpscale = self::profileAllowsUpscale($profile);
        $widths = [];
        foreach (self::responsiveWidths($profile) as $width) {
            if (self::canProduceWidth($media, $width, allowUpscale: $allowUpscale)) {
                $widths[] = $width;
            }
        }

        return $widths;
    }

    /**
     * @return list<string>
     */
    public static function rasterFormatsForProfile(string $profile): array
    {
        return array_values(array_filter(
            self::formatsForProfile($profile),
            static fn (string $format): bool => $format !== 'original',
        ));
    }

    public static function conversionName(string $profile, int|string $sizeOrWidth, string $format): string
    {
        return sprintf('%s-%s-%s', $profile, $sizeOrWidth, $format);
    }

    /**
     * @param  list<string>|null  $profiles
     * @return list<string>
     */
    public static function allConversionNames(?array $profiles = null): array
    {
        $names = [];
        $profileNames = $profiles ?? self::enabledProfileNames();

        if ($profiles === null) {
            foreach (self::sizes() as $name => $definition) {
                $names[] = (string) $name;
            }
        }

        foreach ($profileNames as $profile) {
            if (! is_string($profile) || $profile === '' || ! self::profileEnabled($profile)) {
                continue;
            }

            if (self::profileMode($profile) === self::MODE_RESPONSIVE) {
                foreach (self::responsiveWidths($profile) as $width) {
                    foreach (self::rasterFormatsForProfile($profile) as $format) {
                        $names[] = self::conversionName($profile, $width, $format);
                    }
                }

                continue;
            }

            $names[] = self::singleConversionName($profile);
            if (self::profileSvgMode($profile) === 'png') {
                $names[] = self::svgConversionName($profile);
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @return list<string>
     */
    public static function webFormats(): array
    {
        return self::formatsForProfile(self::PROFILE_WEB);
    }

    /**
     * @return list<string>
     */
    public static function formatsForProfile(string $profile): array
    {
        $formats = self::profileConfig($profile)['formats'] ?? ['avif', 'webp', 'jpg'];

        if (! is_array($formats)) {
            return ['avif', 'webp', 'jpg'];
        }

        $normalized = [];
        foreach ($formats as $format) {
            if (is_string($format) && $format !== '') {
                $normalized[] = strtolower($format) === 'jpeg' ? 'jpg' : strtolower($format);
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
            'image/gif',
        ]);

        if (! is_array($types)) {
            return ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'];
        }

        $normalized = [];
        foreach ($types as $type) {
            if (is_string($type) && $type !== '') {
                $normalized[] = strtolower($type);
            }
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public static function skipMimeTypes(): array
    {
        $types = config('media.delivery.skip_mime_types', ['image/gif']);
        if (! is_array($types)) {
            return ['image/gif'];
        }

        $normalized = [];
        foreach ($types as $type) {
            if (is_string($type) && $type !== '') {
                $normalized[] = strtolower($type);
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

    public static function watermarkPath(?string $profile = null): ?string
    {
        $profile ??= self::PROFILE_CUSTOMER_API;
        $definition = self::profileConfig($profile);
        $watermark = is_array($definition['watermark'] ?? null) ? $definition['watermark'] : [];

        $configured = $watermark['path'] ?? $definition['watermark_path'] ?? null;
        if (! is_string($configured) || $configured === '') {
            return null;
        }

        $path = $configured;
        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        return is_file($path) ? $path : null;
    }

    public static function watermarkEnabled(string $profile): bool
    {
        $definition = self::profileConfig($profile);
        $watermark = $definition['watermark'] ?? null;

        if (is_array($watermark) && array_key_exists('enabled', $watermark)) {
            return (bool) $watermark['enabled'];
        }

        if (is_bool($watermark)) {
            return $watermark;
        }

        return false;
    }

    public static function customerApiEnabled(): bool
    {
        return self::profileEnabled(self::PROFILE_CUSTOMER_API);
    }

    public static function shouldProtectForCustomerApi(?Media $media): bool
    {
        return self::shouldApplyProfile($media, self::PROFILE_CUSTOMER_API);
    }

    public static function shouldApplyProfile(?Media $media, string $profile): bool
    {
        if (! self::profileEnabled($profile)) {
            return false;
        }

        if ($media === null) {
            return true;
        }

        if (! self::isDeliveryEligible($media) && ! self::isSvg($media)) {
            return false;
        }

        if (self::isSvg($media)) {
            return self::profileSvgMode($profile) !== 'skip';
        }

        $definition = self::profileConfig($profile);
        if (! (bool) ($definition['skip_static'] ?? false)) {
            return true;
        }

        $staticPath = $media->getCustomProperty('static_path');

        return ! (is_string($staticPath) && $staticPath !== '');
    }

    private static function registerSvgConversions(HasMedia $model, Media $media): void
    {
        foreach (self::enabledProfileNames() as $profile) {
            if (! self::shouldApplyProfile($media, $profile)) {
                continue;
            }

            if (self::profileSvgMode($profile) !== 'png') {
                continue;
            }

            /** @var Conversion $conversion */
            $conversion = $model->addMediaConversion(self::svgConversionName($profile));
            $conversion->format('png')->queued();
        }
    }

    private static function registerRasterAdminSizes(HasMedia $model, ?Media $media): void
    {
        foreach (self::sizes() as $name => $definition) {
            if (! is_array($definition) || ! isset($definition['width'])) {
                continue;
            }

            $width = (int) $definition['width'];
            if ($media !== null && self::originalWidth($media) === null) {
                // Unknown dimensions: still offer small admin thumbs only.
                if ($width > 300) {
                    continue;
                }
            } elseif (! self::canProduceWidth($media, $width)) {
                continue;
            }

            /** @var Conversion $conversion */
            $conversion = $model->addMediaConversion((string) $name);
            $conversion->nonQueued();

            $height = isset($definition['height']) ? (int) $definition['height'] : null;
            $fit = (string) ($definition['fit'] ?? 'max');

            if ($height !== null && $height > 0) {
                $fitEnum = match ($fit) {
                    'contain' => Fit::Contain,
                    'crop', 'cover' => Fit::Crop,
                    default => Fit::Max,
                };
                $conversion->fit($fitEnum, $width, $height);
            } else {
                $conversion->width($width);
            }

            $quality = self::qualityFor('jpg');
            if ($quality !== null && in_array($name, ['large'], true)) {
                $conversion->quality($quality);
            }
        }
    }

    private static function registerResponsiveProfile(HasMedia $model, ?Media $media, string $profile): void
    {
        $formats = self::rasterFormatsForProfile($profile);

        if ($media !== null && self::needsSameSizeDelivery($media, $profile)) {
            self::registerSameSizeConversions($model, $media, $profile, $formats);

            return;
        }

        // Concrete media with unknown dimensions: no responsive ladder.
        if ($media !== null && self::originalWidth($media) === null) {
            return;
        }

        $allowUpscale = self::profileAllowsUpscale($profile);

        foreach (self::responsiveWidths($profile) as $width) {
            if (! self::canProduceWidth($media, $width, allowUpscale: $allowUpscale)) {
                continue;
            }

            foreach ($formats as $format) {
                self::addRasterConversion($model, self::conversionName($profile, $width, $format), $width, $format);
            }
        }
    }

    /**
     * @param  list<string>  $formats
     */
    private static function registerSameSizeConversions(HasMedia $model, Media $media, string $profile, array $formats): void
    {
        $width = self::originalWidth($media);
        if ($width === null || $width < 1) {
            return;
        }

        foreach ($formats as $format) {
            self::addRasterConversion($model, self::conversionName($profile, $width, $format), $width, $format);
        }
    }

    private static function addRasterConversion(HasMedia $model, string $name, int $width, string $format): void
    {
        /** @var Conversion $conversion */
        $conversion = $model->addMediaConversion($name);
        $conversion->width($width)->format($format)->queued();

        $quality = self::qualityFor($format);
        if ($quality !== null) {
            $conversion->quality($quality);
        }
    }

    private static function registerSingleProfile(HasMedia $model, ?Media $media, string $profile): void
    {
        $definition = self::profileConfig($profile);
        $width = (int) ($definition['default_width'] ?? 1024);
        $allowUpscale = self::profileAllowsUpscale($profile);

        if ($media !== null && self::originalWidth($media) === null) {
            // Unknown size: still produce a single delivery at default width (downscale-safe for most files).
        } elseif (! self::canProduceWidth($media, $width, allowUpscale: $allowUpscale)) {
            $originalWidth = $media !== null ? self::originalWidth($media) : null;
            $width = $originalWidth ?? $width;
        }

        $format = self::preferredSingleFormat($media, $profile);

        /** @var Conversion $conversion */
        $conversion = $model->addMediaConversion(self::singleConversionName($profile));
        $conversion->width(max(1, $width))->format($format)->queued();

        MediaWatermark::fromProfile($profile)?->applyToConversion($conversion);

        $quality = self::qualityFor($format);
        if ($quality !== null) {
            $conversion->quality($quality);
        }
    }

    private static function preferredSingleFormat(?Media $media, string $profile): string
    {
        $formats = array_values(array_filter(
            self::formatsForProfile($profile),
            static fn (string $format): bool => ! in_array($format, ['original', 'avif', 'webp'], true),
        ));

        $mime = strtolower((string) ($media?->mime_type ?? ''));
        if (($mime === 'image/png' || str_contains($mime, 'png')) && in_array('png', $formats, true)) {
            return 'png';
        }

        if (in_array('jpg', $formats, true)) {
            return 'jpg';
        }

        return $formats[0] ?? 'jpg';
    }
}
