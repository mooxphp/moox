<?php

namespace Moox\Core\Support\Resources;

class ScopedResourceRegistry
{
    /**
     * @var array<class-string, array<string, array<string, mixed>>>
     */
    protected static array $definitions = [];

    /**
     * @param  class-string  $resource
     * @param  array<string, mixed>  $definition
     */
    public static function register(string $resource, string $key, array $definition = []): void
    {
        static::$definitions[$resource][$key] = $definition;
    }

    /**
     * @param  class-string  $resource
     * @return array<string, mixed>
     */
    public static function get(string $resource, string $key): array
    {
        return static::$definitions[$resource][$key] ?? [];
    }

    /**
     * @param  class-string  $resource
     */
    public static function getValue(string $resource, string $key, string $definitionKey): mixed
    {
        return static::$definitions[$resource][$key][$definitionKey] ?? null;
    }
}
