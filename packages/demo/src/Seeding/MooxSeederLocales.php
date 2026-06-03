<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Moox\Demo\DemoServiceProvider;

final class MooxSeederLocales
{
    /** @var list<string> */
    public const DEFAULT = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    public static function isInstalled(): bool
    {
        return class_exists(DemoServiceProvider::class);
    }

    /**
     * moox/demo installed → config demo.default_locales; otherwise seeder LOCALES const.
     *
     * @param  list<string>  $fallback
     * @return list<string>
     */
    public static function resolve(array $fallback): array
    {
        if (self::isInstalled()) {
            $configured = config('demo.default_locales', self::DEFAULT);

            if (is_array($configured) && $configured !== []) {
                return self::normalizeList($configured);
            }
        }

        return self::normalizeList($fallback);
    }

    /**
     * Merges CLI/context locales with config default_locales (DemoLocalizationStep).
     *
     * @param  list<string>  $contextLocales
     * @return list<string>
     */
    public static function mergeForDemoRun(array $contextLocales): array
    {
        $configured = config('demo.default_locales', self::DEFAULT);

        if (! is_array($configured)) {
            $configured = [];
        }

        return self::normalizeList(array_merge($contextLocales, $configured));
    }

    /**
     * @param  array<int, mixed>  $locales
     * @return list<string>
     */
    private static function normalizeList(array $locales): array
    {
        return array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $locale): string => is_string($locale) ? trim($locale) : '',
                $locales,
            ),
            static fn (string $locale): bool => $locale !== '',
        )));
    }
}
