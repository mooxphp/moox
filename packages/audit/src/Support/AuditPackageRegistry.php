<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

final class AuditPackageRegistry
{
    /** @var array<string, array<string, mixed>> */
    private static array $packages = [];

    /**
     * @param  array<string, mixed>  $defaults
     */
    public static function register(string $packageKey, array $defaults): void
    {
        self::$packages[$packageKey] = array_replace_recursive(
            self::$packages[$packageKey] ?? [],
            $defaults,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function modelConfig(string $modelClass): array
    {
        $config = self::mergedModels()[$modelClass] ?? [];

        return is_array($config) ? $config : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function mergedModels(): array
    {
        return self::mergedSection('models');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function mergedHooks(): array
    {
        return self::mergedSection('hooks');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function mergedFilament(): array
    {
        return self::mergedSection('filament');
    }

    public static function clear(): void
    {
        self::$packages = [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function mergedSection(string $section): array
    {
        $merged = [];

        foreach (self::$packages as $package) {
            if (! isset($package[$section]) || ! is_array($package[$section])) {
                continue;
            }

            foreach ($package[$section] as $key => $config) {
                if (! is_string($key) || ! is_array($config)) {
                    continue;
                }

                $merged[$key] = array_replace_recursive($merged[$key] ?? [], $config);
            }
        }

        return $merged;
    }
}
