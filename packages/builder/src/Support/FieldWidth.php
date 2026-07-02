<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

/**
 * Per-field layout width. Fields render on a fixed 12-column grid; the width
 * fraction is translated into a Filament columnSpan. Field widths therefore
 * define the column layout (two 1/2 fields sit side by side), so no separate
 * "column count" setting is needed.
 *
 * Stored in the field settings as `width`; defaults to full width, which keeps
 * existing fields rendering exactly as before.
 */
final class FieldWidth
{
    public const GRID_COLUMNS = 12;

    public const FULL = 'full';

    /** @var array<string, int> */
    public const SPANS = [
        self::FULL => 12,
        '1/2' => 6,
        '1/3' => 4,
        '2/3' => 8,
        '1/4' => 3,
        '3/4' => 9,
    ];

    public static function normalize(?string $width): string
    {
        return isset(self::SPANS[$width]) ? $width : self::FULL;
    }

    public static function columnSpan(?string $width): int
    {
        return self::SPANS[self::normalize($width)];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::SPANS);
    }
}
