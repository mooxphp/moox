<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Spatie\Image\Drivers\ImageDriver;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Unit;
use Spatie\MediaLibrary\Conversions\Conversion;

/**
 * Opt-in watermark applicator based on Spatie Image watermark API.
 *
 * Profiles that do not enable watermark / have no path simply skip it —
 * consumers never get a package brand asset.
 *
 * @see https://spatie.be/docs/image/v3/image-manipulations/adding-a-watermark
 */
final class MediaWatermark
{
    public function __construct(
        public readonly string $path,
        public readonly AlignPosition $position = AlignPosition::BottomRight,
        public readonly int $paddingPercent = 3,
        public readonly int $widthPercent = 15,
        public readonly int $opacity = 40,
        public readonly Fit $fit = Fit::Contain,
    ) {
    }

    /**
     * Build from a delivery profile config, or null when watermark is off / path missing.
     */
    public static function fromProfile(string $profile): ?self
    {
        if (! MediaDeliveryConversions::watermarkEnabled($profile)) {
            return null;
        }

        $path = MediaDeliveryConversions::watermarkPath($profile);
        if ($path === null) {
            return null;
        }

        $definition = MediaDeliveryConversions::profileConfig($profile);
        $watermark = is_array($definition['watermark'] ?? null) ? $definition['watermark'] : [];

        return new self(
            path: $path,
            position: self::resolvePosition($watermark['position'] ?? $definition['watermark_position'] ?? null),
            paddingPercent: (int) ($watermark['padding_percent'] ?? $definition['watermark_padding_percent'] ?? 3),
            widthPercent: (int) ($watermark['width_percent'] ?? $definition['watermark_width_percent'] ?? 15),
            opacity: (int) ($watermark['opacity'] ?? $definition['watermark_opacity'] ?? 40),
            fit: self::resolveFit($watermark['fit'] ?? null),
        );
    }

    /**
     * Apply to a Media Library conversion (queued with other manipulations).
     */
    public function applyToConversion(Conversion $conversion): Conversion
    {
        $conversion->watermark(...$this->watermarkArguments());

        return $conversion;
    }

    /**
     * Apply directly via Spatie Image (outside conversion registration).
     */
    public function applyToImage(ImageDriver $image): ImageDriver
    {
        $image->watermark(...$this->watermarkArguments());

        return $image;
    }

    /**
     * @return array{
     *     0: string,
     *     1: AlignPosition,
     *     2: int,
     *     3: int,
     *     4: Unit,
     *     5: int,
     *     6: Unit,
     *     7: int,
     *     8: Unit,
     *     9: Fit,
     *     10: int
     * }
     */
    public function watermarkArguments(): array
    {
        return [
            $this->path,
            $this->position,
            $this->paddingPercent,
            $this->paddingPercent,
            Unit::Percent,
            $this->widthPercent,
            Unit::Percent,
            0,
            Unit::Pixel,
            $this->fit,
            $this->opacity,
        ];
    }

    private static function resolvePosition(mixed $value): AlignPosition
    {
        if ($value instanceof AlignPosition) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return AlignPosition::BottomRight;
        }

        $normalized = strtolower(str_replace(['-', ' '], '_', $value));

        return match ($normalized) {
            'top', 'top_center' => AlignPosition::Top,
            'top_left' => AlignPosition::TopLeft,
            'top_right' => AlignPosition::TopRight,
            'center', 'centre' => AlignPosition::Center,
            'left' => AlignPosition::Left,
            'right' => AlignPosition::Right,
            'bottom', 'bottom_center' => AlignPosition::Bottom,
            'bottom_left' => AlignPosition::BottomLeft,
            default => AlignPosition::BottomRight,
        };
    }

    private static function resolveFit(mixed $value): Fit
    {
        if ($value instanceof Fit) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return Fit::Contain;
        }

        return match (strtolower($value)) {
            'max' => Fit::Max,
            'fill' => Fit::Fill,
            'stretch' => Fit::Stretch,
            'crop' => Fit::Crop,
            default => Fit::Contain,
        };
    }
}
