<?php

declare(strict_types=1);

namespace Moox\Core\Relations;

final class RelationRegistry
{
    /** @var array<string, array<string, mixed>> */
    protected static array $blueprints = [];

    /** @var array<class-string, array<string, mixed>> */
    protected static array $relatedDefaults = [];

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function blueprint(string $name, array $definition): void
    {
        self::$blueprints[$name] = array_replace(
            self::$blueprints[$name] ?? [],
            $definition,
        );
    }

    /**
     * @param  class-string  $relatedModel
     * @param  array<string, mixed>  $defaults
     *
     * @deprecated Configure {@see defaultsForRelatedModel()} via `{resource}.related_morph_defaults` in config.
     */
    public static function relatedDefaults(string $relatedModel, array $defaults): void
    {
        self::$relatedDefaults[$relatedModel] = array_replace(
            self::$relatedDefaults[$relatedModel] ?? [],
            $defaults,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultsForRelatedModel(?string $relatedModel): array
    {
        if (! is_string($relatedModel) || $relatedModel === '') {
            return [];
        }

        $defaults = self::$relatedDefaults[$relatedModel] ?? [];

        $resource = self::resourceNameForModel($relatedModel);

        if ($resource === null) {
            return $defaults;
        }

        $fromConfig = config("{$resource}.related_morph_defaults", []);

        if (! is_array($fromConfig) || $fromConfig === []) {
            return $defaults;
        }

        return array_replace_recursive($fromConfig, $defaults);
    }

    /**
     * @return array<string, mixed>
     */
    public static function blueprintDefinition(string $name): array
    {
        $fromConfig = config("core.relations.blueprints.{$name}", []);

        if (! is_array($fromConfig)) {
            $fromConfig = [];
        }

        return array_replace_recursive($fromConfig, self::$blueprints[$name] ?? []);
    }

    /**
     * @param  class-string  $model
     */
    private static function resourceNameForModel(string $model): ?string
    {
        if (! class_exists($model) || ! method_exists($model, 'getResourceName')) {
            return null;
        }

        $resource = $model::getResourceName();

        return is_string($resource) && $resource !== '' ? $resource : null;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function merge(array $config): array
    {
        $uses = $config['uses'] ?? $config['extends'] ?? null;

        if (is_string($uses) && $uses !== '') {
            $config = self::mergeDefinition(self::blueprintDefinition($uses), $config);
        }

        $kind = $config['kind'] ?? null;

        if (is_string($kind) && $kind !== '') {
            $config = self::mergeDefinition(self::blueprintDefinition($kind), $config);
        }

        $relatedModel = $config['related_model'] ?? $config['model'] ?? null;

        if (is_string($relatedModel) && $relatedModel !== '') {
            $config = self::mergeDefinition(self::defaultsForRelatedModel($relatedModel), $config);
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private static function mergeDefinition(array $definition, array $config): array
    {
        $actions = array_key_exists('actions', $config) ? $config['actions'] : null;

        $config = array_replace_recursive($definition, $config);

        if (is_array($actions)) {
            $config['actions'] = $actions;
        }

        return $config;
    }
}
