<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\Concerns;

use BackedEnum;

/**
 * Navigation and labels from `e-billing.resources.<resourceConfigKey()>`, falling back to
 * {@see resourceConfigDefaults()} when a host config predates a key.
 */
trait ReadsEBillingResourceConfig
{
    abstract public static function resourceConfigKey(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected static function resourceConfigDefaults(): array;

    protected static function resourceConfig(string $key): mixed
    {
        return config('e-billing.resources.'.static::resourceConfigKey().'.'.$key)
            ?? static::resourceConfigDefaults()[$key]
            ?? null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) (static::resourceConfig('enabled') ?? true);
    }

    public static function getNavigationSort(): ?int
    {
        $sort = static::resourceConfig('navigation_sort');

        return is_int($sort) ? $sort : (is_numeric($sort) ? (int) $sort : null);
    }

    public static function getModelLabel(): string
    {
        return self::resolveConfigLabel((string) static::resourceConfig('label'));
    }

    public static function getPluralModelLabel(): string
    {
        return self::resolveConfigLabel((string) static::resourceConfig('plural_label'));
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        $group = static::resourceConfig('navigation_group');

        return is_string($group) && $group !== '' ? self::resolveConfigLabel($group) : null;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        $icon = static::resourceConfig('navigation_icon');

        if ($icon === '') {
            return static::resourceConfigDefaults()['navigation_icon'] ?? null;
        }

        return is_string($icon) || $icon instanceof BackedEnum ? $icon : null;
    }

    private static function resolveConfigLabel(string $value): string
    {
        if (str_starts_with($value, 'trans//')) {
            return __(substr($value, 7));
        }

        return $value;
    }
}
