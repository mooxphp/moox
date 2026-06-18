<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

final class AuditConfigResolver
{
    /**
     * @return array<string, mixed>|null
     */
    public static function resolveModel(string $modelClass): ?array
    {
        if (! config('audit.enabled', true)) {
            return null;
        }

        $packageConfig = AuditPackageRegistry::modelConfig($modelClass);
        $appConfig = config("audit.models.{$modelClass}", []);
        $appConfig = is_array($appConfig) ? $appConfig : [];

        if ($packageConfig === [] && $appConfig === []) {
            return null;
        }

        $presetKey = self::resolvePresetKey($packageConfig, $appConfig);
        $preset = $presetKey !== null ? config("audit.presets.{$presetKey}", []) : [];
        $preset = is_array($preset) ? $preset : [];

        $merged = AuditConfigMerger::merge($preset, $packageConfig, $appConfig);
        $merged['user_models'] = config('audit.user_models', []);

        if (! ($merged['enabled'] ?? true)) {
            return null;
        }

        return $merged;
    }

    /**
     * @return list<class-string>
     */
    public static function allTrackedModelClasses(): array
    {
        $classes = [
            ...array_keys(AuditPackageRegistry::mergedModels()),
            ...array_keys(config('audit.models', [])),
        ];

        return array_values(array_unique(array_filter($classes, is_string(...))));
    }

    /**
     * @return array<class-string, array<string, array<string, mixed>>>
     */
    public static function resolvedHooks(): array
    {
        return self::resolveKeyedSection(
            AuditPackageRegistry::mergedHooks(),
            config('audit.hooks', []),
        );
    }

    /**
     * @return array<class-string, array<string, mixed>>
     */
    public static function resolvedFilament(): array
    {
        $package = AuditPackageRegistry::mergedFilament();
        $app = config('audit.filament', []);
        $app = is_array($app) ? $app : [];

        $keys = array_unique([
            ...array_keys($package),
            ...array_keys($app),
        ]);

        $resolved = [];

        foreach ($keys as $resourceClass) {
            if (! is_string($resourceClass)) {
                continue;
            }

            $merged = AuditConfigMerger::merge(
                [],
                is_array($package[$resourceClass] ?? null) ? $package[$resourceClass] : [],
                is_array($app[$resourceClass] ?? null) ? $app[$resourceClass] : [],
            );

            if ($merged['enabled'] ?? true) {
                $resolved[$resourceClass] = $merged;
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, array<string, mixed>>  $package
     * @param  array<string, mixed>  $app
     * @return array<string, array<string, array<string, mixed>>>
     */
    private static function resolveKeyedSection(array $package, array $app): array
    {
        $app = is_array($app) ? $app : [];

        $keys = array_unique([
            ...array_keys($package),
            ...array_keys($app),
        ]);

        $resolved = [];

        foreach ($keys as $key) {
            if (! is_string($key)) {
                continue;
            }

            $packageItems = is_array($package[$key] ?? null) ? $package[$key] : [];
            $appItems = is_array($app[$key] ?? null) ? $app[$key] : [];
            $events = array_unique([
                ...array_keys($packageItems),
                ...array_keys($appItems),
            ]);

            foreach ($events as $event) {
                if (! is_string($event)) {
                    continue;
                }

                $merged = AuditConfigMerger::merge(
                    [],
                    is_array($packageItems[$event] ?? null) ? $packageItems[$event] : [],
                    is_array($appItems[$event] ?? null) ? $appItems[$event] : [],
                );

                if ($merged['enabled'] ?? true) {
                    $resolved[$key][$event] = $merged;
                }
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $packageConfig
     * @param  array<string, mixed>  $appConfig
     */
    private static function resolvePresetKey(array $packageConfig, array $appConfig): ?string
    {
        $preset = $appConfig['preset'] ?? $packageConfig['preset'] ?? null;

        return is_string($preset) && $preset !== '' ? $preset : null;
    }
}
