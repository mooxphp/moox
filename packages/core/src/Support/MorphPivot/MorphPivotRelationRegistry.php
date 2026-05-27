<?php

declare(strict_types=1);

namespace Moox\Core\Support\MorphPivot;

/**
 * Optional defaults per related model (registered by packages, e.g. moox/address).
 */
class MorphPivotRelationRegistry
{
    /** @var array<class-string, array<string, mixed>> */
    protected static array $relatedModels = [];

    /**
     * @param  class-string  $relatedModel
     * @param  array<string, mixed>  $defaults
     */
    public static function registerRelatedModel(string $relatedModel, array $defaults): void
    {
        static::$relatedModels[$relatedModel] = array_replace(
            static::$relatedModels[$relatedModel] ?? [],
            $defaults,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultsFor(?string $relatedModel): array
    {
        if (! is_string($relatedModel) || $relatedModel === '') {
            return [];
        }

        return static::$relatedModels[$relatedModel] ?? [];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function mergeConfig(array $config): array
    {
        $model = $config['model'] ?? null;

        if (! is_string($model) || $model === '') {
            return $config;
        }

        return array_replace_recursive(static::defaultsFor($model), $config);
    }
}
