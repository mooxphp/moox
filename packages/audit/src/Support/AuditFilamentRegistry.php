<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;

final class AuditFilamentRegistry
{
    /** @var array<class-string, array<string, mixed>> */
    private static array $configs = [];

    /**
     * @param  class-string  $resourceClass
     * @param  array<string, mixed>  $config
     */
    public static function register(string $resourceClass, array $config): void
    {
        self::$configs[$resourceClass] = $config;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function configForOwner(Model $owner): ?array
    {
        foreach (self::$configs as $config) {
            $ownerModel = $config['owner_model'] ?? null;

            if (is_string($ownerModel) && $owner instanceof $ownerModel) {
                return $config;
            }
        }

        return null;
    }

    public static function clear(): void
    {
        self::$configs = [];
    }
}
