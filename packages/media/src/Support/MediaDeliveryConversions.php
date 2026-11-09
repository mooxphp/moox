<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Unit;
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

    public const MIN_RESPONSIVE_WIDTH = 150;

    public static function register(HasMedia $model, ?Media $media = null): void
    {
        if ($media !== null && self::isSvg($media)) {
            self::registerSvgConversions($model);

            return;
        }

        if ($media !== null && ! self::isRaster($media)) {
            return;
        }

        self::registerRasterAdminSizes($model, $media);
        self::registerRasterWebConversions($model, $media);
        self::registerRasterMailConversion($model, $media);
        self::registerCustomerApiConversion($model, $media);
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
     * @return list<int>
     */
    public static function responsiveWidths(): array
    {
        $widths = [];

        foreach (self::sizes() as $definition) {
            if (! is_array($definition) || ! isset($definition['width'])) {
                continue;
            }

            $width = (int) $definition['width'];
            if ($width >= self::minResponsiveWidth()) {
                $widths[] = $width;
            }
        }

        $extra = config('media.delivery.extra_widths', []);
        if (is_array($extra)) {
            foreach ($extra as $width) {
                $widths[] = (int) $width;
            }
        }

        $widths = array_values(array_unique(array_filter($widths, static fn (int $w): bool => $w > 0)));
        sort($widths);

        return $widths;
    }

    public static function minResponsiveWidth(): int
    {
        return (int) config('media.delivery.profiles.web.min_responsive_width', self::MIN_RESPONSIVE_WIDTH);
    }

    public static function canProduceWidth(?Media $media, int $targetWidth, bool $allowUpscale = false): bool
    {
        if ($allowUpscale || $media === null) {
            return true;
        }

        $originalWidth = self::originalWidth($media);
        if ($originalWidth === null) {
            return true;
        }

        return $originalWidth >= $targetWidth;
    }

    /**
     * Widths that should appear in a web srcset for this media (no upscale, skip tiny sources).
     *
     * @return list<int>
     */
    public static function applicableResponsiveWidths(Media $media): array
    {
        $originalWidth = self::originalWidth($media);
        if ($originalWidth !== null && $originalWidth < self::minResponsiveWidth()) {
            return [];
        }

        $widths = [];
        foreach (self::responsiveWidths() as $width) {
            if (self::canProduceWidth($media, $width, allowUpscale: false)) {
                $widths[] = $width;
            }
        }

        return $widths;
    }

    public static function conversionName(string $profile, int|string $sizeOrWidth, string $format): string
    {
        return sprintf('%s-%s-%s', $profile, $sizeOrWidth, $format);
    }

    /**
     * @return list<string>
     */
    public static function allConversionNames(): array
    {
        $names = [
            self::MAIL_PNG,
            self::MAIL_RASTER,
            self::CUSTOMER_API,
        ];

        foreach (self::sizes() as $name => $definition) {
            $names[] = $name;
        }

        foreach (self::responsiveWidths() as $width) {
            foreach (self::webFormats() as $format) {
                if ($format === 'original') {
                    continue;
                }
                $names[] = self::conversionName(self::PROFILE_WEB, $width, $format);
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
        $formats = config("media.delivery.profiles.{$profile}.formats", ['avif', 'webp', 'jpg']);

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

    public static function watermarkPath(): ?string
    {
        $configured = config('media.delivery.profiles.customer_api.watermark_path');
        if (! is_string($configured) || $configured === '') {
            return null;
        }

        $path = $configured;
        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        return is_file($path) ? $path : null;
    }

    public static function customerApiEnabled(): bool
    {
        return (bool) config('media.delivery.profiles.customer_api.enabled', false);
    }

    private static function registerSvgConversions(HasMedia $model): void
    {
        /** @var Conversion $mail */
        $mail = $model->addMediaConversion(self::MAIL_PNG);
        $mail->format('png')->queued();

        // SVGs are brand/UI assets — customer API protection targets product rasters only.
    }

    private static function registerRasterAdminSizes(HasMedia $model, ?Media $media): void
    {
        foreach (self::sizes() as $name => $definition) {
            if (! is_array($definition) || ! isset($definition['width'])) {
                continue;
            }

            $width = (int) $definition['width'];
            if (! self::canProduceWidth($media, $width)) {
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

    private static function registerRasterWebConversions(HasMedia $model, ?Media $media): void
    {
        $formats = array_values(array_filter(
            self::webFormats(),
            static fn (string $format): bool => $format !== 'original',
        ));

        foreach (self::responsiveWidths() as $width) {
            if (! self::canProduceWidth($media, $width)) {
                continue;
            }

            foreach ($formats as $format) {
                /** @var Conversion $conversion */
                $conversion = $model->addMediaConversion(self::conversionName(self::PROFILE_WEB, $width, $format));
                $conversion->width($width)->format($format)->queued();

                $quality = self::qualityFor($format);
                if ($quality !== null) {
                    $conversion->quality($quality);
                }
            }
        }
    }

    private static function registerRasterMailConversion(HasMedia $model, ?Media $media): void
    {
        $width = (int) config('media.delivery.profiles.mail.default_width', 600);
        if (! self::canProduceWidth($media, $width)) {
            $originalWidth = $media !== null ? self::originalWidth($media) : null;
            $width = $originalWidth ?? $width;
        }

        $format = self::preferredMailFormat($media);

        /** @var Conversion $conversion */
        $conversion = $model->addMediaConversion(self::MAIL_RASTER);
        $conversion->width(max(1, $width))->format($format)->queued();

        $quality = self::qualityFor($format);
        if ($quality !== null) {
            $conversion->quality($quality);
        }
    }

    private static function registerCustomerApiConversion(HasMedia $model, ?Media $media): void
    {
        if (! self::customerApiEnabled()) {
            return;
        }

        if (! self::shouldProtectForCustomerApi($media)) {
            return;
        }

        $width = (int) config('media.delivery.profiles.customer_api.default_width', 1024);
        if (! self::canProduceWidth($media, $width)) {
            $originalWidth = $media !== null ? self::originalWidth($media) : null;
            $width = $originalWidth ?? $width;
        }

        $format = self::preferredMailFormat($media);

        /** @var Conversion $conversion */
        $conversion = $model->addMediaConversion(self::CUSTOMER_API);
        $conversion->width(max(1, $width))->format($format)->queued();

        if ((bool) config('media.delivery.profiles.customer_api.watermark', false)) {
            self::applyWatermark($conversion);
        }

        $quality = self::qualityFor($format);
        if ($quality !== null) {
            $conversion->quality($quality);
        }
    }

    /**
     * Whether this media should get a customer_api conversion when the profile is enabled.
     * Static-site imports (custom property static_path) are skipped when skip_static is true.
     */
    public static function shouldProtectForCustomerApi(?Media $media): bool
    {
        if (! self::customerApiEnabled()) {
            return false;
        }

        if ($media === null) {
            return true;
        }

        if (self::isSvg($media)) {
            return false;
        }

        if (! (bool) config('media.delivery.profiles.customer_api.skip_static', true)) {
            return true;
        }

        $staticPath = $media->getCustomProperty('static_path');

        return ! (is_string($staticPath) && $staticPath !== '');
    }

    private static function applyWatermark(Conversion $conversion): void
    {
        $watermarkPath = self::watermarkPath();
        if ($watermarkPath === null) {
            return;
        }

        $padding = (int) config('media.delivery.profiles.customer_api.watermark_padding_percent', 3);

        $conversion->watermark(
            $watermarkPath,
            AlignPosition::BottomRight,
            $padding,
            $padding,
            Unit::Percent,
            (int) config('media.delivery.profiles.customer_api.watermark_width_percent', 15),
            Unit::Percent,
            0,
            Unit::Pixel,
            Fit::Contain,
            (int) config('media.delivery.profiles.customer_api.watermark_opacity', 40),
        );
    }

    private static function preferredMailFormat(?Media $media): string
    {
        $mime = strtolower((string) ($media?->mime_type ?? ''));

        if ($mime === 'image/png' || str_contains($mime, 'png')) {
            return 'png';
        }

        return 'jpg';
    }
}
