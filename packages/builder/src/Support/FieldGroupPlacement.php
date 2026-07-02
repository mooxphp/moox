<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

/**
 * Where a field group's section is rendered in a consumer form. The consumer
 * owns the layout and pulls each placement into its own slot via
 * HasCustomFields::customFieldComponents($placement).
 *
 * The legacy stored value "default" is treated as MAIN, so existing groups keep
 * rendering in the main area without a migration.
 */
final class FieldGroupPlacement
{
    public const MAIN = 'main';

    public const SIDEBAR = 'sidebar';

    /** @var list<string> */
    public const PLACEMENTS = [self::MAIN, self::SIDEBAR];

    public static function normalize(?string $placement): string
    {
        return in_array($placement, self::PLACEMENTS, true) ? $placement : self::MAIN;
    }

    public static function matches(?string $stored, string $placement): bool
    {
        return self::normalize($stored) === self::normalize($placement);
    }
}
