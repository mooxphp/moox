<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Moox\BlockEditor\EntityQuery\Contracts\EntityQuerySource;
use Moox\BlockEditor\EntityQuery\Sources\ConfigDraftEntityQuerySource;

final class EntityQuerySourceRegistry
{
    /** @var array<string, array<string, mixed>> */
    private static array $sources = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public static function register(string $key, array $config): void
    {
        self::$sources[$key] = array_replace_recursive(self::$sources[$key] ?? [], $config);
    }

    public static function resolve(string $key): EntityQuerySource
    {
        if (! isset(self::$sources[$key])) {
            throw new InvalidArgumentException("Unknown dynamic feed source [{$key}].");
        }

        return new ConfigDraftEntityQuerySource($key, self::$sources[$key]);
    }

    /**
     * @return Collection<int, EntityQuerySource>
     */
    public static function sources(): Collection
    {
        return collect(self::$sources)
            ->keys()
            ->map(fn (string $key): EntityQuerySource => self::resolve($key))
            ->values();
    }

    public static function has(string $key): bool
    {
        return isset(self::$sources[$key]) && (self::$sources[$key]['enabled'] ?? true);
    }

    public static function clear(): void
    {
        self::$sources = [];
    }
}
