<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class AuditConfigResolver
{
    /** @var list<string> */
    private const DEFAULT_EXCLUDED_ATTRIBUTES = [
        'id',
        'uuid',
        'ulid',
        'created_at',
        'updated_at',
        'deleted_at',
        'custom_properties',
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, mixed>|null
     */
    public static function resolveModel(string $modelClass): ?array
    {
        if (! config('audit.enabled', true)) {
            return null;
        }

        $packageModels = AuditPackageRegistry::mergedModels();
        $appModels = config('audit.models', []);
        $appModels = is_array($appModels) ? $appModels : [];

        $hasPackage = array_key_exists($modelClass, $packageModels);
        $hasApp = array_key_exists($modelClass, $appModels);

        if (! $hasPackage && ! $hasApp) {
            return null;
        }

        $packageConfig = $hasPackage && is_array($packageModels[$modelClass])
            ? $packageModels[$modelClass]
            : [];
        $appConfig = $hasApp && is_array($appModels[$modelClass])
            ? $appModels[$modelClass]
            : [];

        $presetKey = self::resolvePresetKey($packageConfig, $appConfig);
        $preset = $presetKey !== null ? config("audit.presets.{$presetKey}", []) : [];
        $preset = is_array($preset) ? $preset : [];

        $merged = AuditConfigMerger::merge($preset, $packageConfig, $appConfig);
        $merged = self::applyModelDefaults($modelClass, $merged, $packageConfig, $appConfig);
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

            $merged = self::applyFilamentDefaults($resourceClass, $merged);

            if ($merged['enabled'] ?? true) {
                $resolved[$resourceClass] = $merged;
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $packageConfig
     * @param  array<string, mixed>  $appConfig
     * @param  array<string, mixed>  $merged
     * @return array<string, mixed>
     */
    private static function applyModelDefaults(
        string $modelClass,
        array $merged,
        array $packageConfig,
        array $appConfig,
    ): array {
        if (! array_key_exists('entry_type', $merged) || ! is_string($merged['entry_type']) || $merged['entry_type'] === '') {
            $merged['entry_type'] = (string) config('audit.default_entry_type', 'audit');
        }

        if (! array_key_exists('events', $merged)) {
            $merged['events'] = self::defaultEvents($modelClass);
        }

        if (! array_key_exists('log_name', $merged) || ! is_string($merged['log_name']) || $merged['log_name'] === '') {
            $merged['log_name'] = self::defaultLogName($modelClass);
        }

        if (! array_key_exists('attributes', $packageConfig) && ! array_key_exists('attributes', $appConfig) && ! array_key_exists('attributes', $merged)) {
            $merged['attributes'] = self::defaultAttributes($modelClass);
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private static function applyFilamentDefaults(string $resourceClass, array $config): array
    {
        $ownerModel = $config['owner_model'] ?? null;

        if (is_string($ownerModel) && $ownerModel !== '') {
            return $config;
        }

        if (! class_exists($resourceClass) || ! method_exists($resourceClass, 'getModel')) {
            return $config;
        }

        $model = $resourceClass::getModel();

        if (is_string($model) && $model !== '' && is_subclass_of($model, Model::class)) {
            $config['owner_model'] = $model;
        }

        return $config;
    }

    /**
     * @return list<string>
     */
    private static function defaultEvents(string $modelClass): array
    {
        $events = ['created', 'updated', 'deleted'];

        if (class_exists($modelClass) && in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $events[] = 'restored';
        }

        return $events;
    }

    private static function defaultLogName(string $modelClass): string
    {
        if (class_exists($modelClass) && method_exists($modelClass, 'getResourceName')) {
            $resourceName = $modelClass::getResourceName();

            if (is_string($resourceName) && $resourceName !== '') {
                return $resourceName;
            }
        }

        return Str::kebab(class_basename($modelClass));
    }

    /**
     * @return list<string>
     */
    private static function defaultAttributes(string $modelClass): array
    {
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        /** @var Model $model */
        $model = new $modelClass;

        return array_values(array_diff(
            $model->getFillable(),
            self::DEFAULT_EXCLUDED_ATTRIBUTES,
        ));
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
