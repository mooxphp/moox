<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Config;

use Heco\FilamentTreeIndex\Contracts\ConfiguresTreeIndex;
use LogicException;

final class TreeIndexConfigurationRegistry
{
    /** @var array<string, TreeIndexConfiguration> */
    private static array $configurations = [];

    public static function register(string $key, TreeIndexConfiguration $configuration): void
    {
        self::$configurations[$key] = $configuration;
    }

    public static function get(string $key): TreeIndexConfiguration
    {
        return self::resolve($key);
    }

    public static function resolve(string $key): TreeIndexConfiguration
    {
        if (isset(self::$configurations[$key])) {
            return self::$configurations[$key];
        }

        if (! is_a($key, ConfiguresTreeIndex::class, true)) {
            throw new LogicException("Tree index configuration [{$key}] is not registered.");
        }

        /** @var class-string<ConfiguresTreeIndex> $key */
        self::register($key, $key::treeIndex());

        return self::$configurations[$key];
    }

    public static function forget(string $key): void
    {
        unset(self::$configurations[$key]);
    }
}
