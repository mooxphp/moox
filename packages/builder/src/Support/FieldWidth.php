<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

/**
 * Layout widths on a fixed 12-column grid.
 *
 * Two levels cooperate:
 *  - A field group defines how many columns its fields flow into
 *    (`settings.columns`, 1–4). This sets the default span every field inherits.
 *  - A single field may override its width (`settings.width`) to span a specific
 *    fraction; the special `auto` value (the default) keeps it following the
 *    group's column count.
 *
 * Both are translated into Filament columnSpans, so no separate grid component
 * is needed: a group with 2 columns puts two fields side by side automatically.
 */
final class FieldWidth
{
    public const GRID_COLUMNS = 12;

    public const FULL = 'full';

    /**
     * Sentinel width meaning "inherit the group's column count" instead of a
     * fixed fraction. Stored when a field should follow the group layout.
     */
    public const AUTO = 'auto';

    /** @var array<string, int> */
    public const SPANS = [
        self::FULL => 12,
        '1/2' => 6,
        '1/3' => 4,
        '2/3' => 8,
        '1/4' => 3,
        '3/4' => 9,
    ];

    /** @var list<int> */
    public const GROUP_COLUMNS = [1, 2, 3, 4];

    public static function normalize(?string $width): string
    {
        return isset(self::SPANS[$width]) ? $width : self::FULL;
    }

    public static function columnSpan(?string $width): int
    {
        return self::SPANS[self::normalize($width)];
    }

    /**
     * Whether the given raw width value is a fixed fraction (an explicit
     * override) rather than the inherit-from-group default.
     */
    public static function isExplicit(mixed $width): bool
    {
        return is_string($width) && isset(self::SPANS[$width]);
    }

    public static function normalizeColumns(mixed $columns): int
    {
        $columns = is_numeric($columns) ? (int) $columns : 1;

        return in_array($columns, self::GROUP_COLUMNS, true) ? $columns : 1;
    }

    /**
     * Default columnSpan a field inherits when its group uses the given number
     * of columns (2 columns → span 6, so two fields share a row).
     */
    public static function columnsToSpan(mixed $columns): int
    {
        return intdiv(self::GRID_COLUMNS, self::normalizeColumns($columns));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::SPANS);
    }
}
