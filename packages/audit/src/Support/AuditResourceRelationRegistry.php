<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;

final class AuditResourceRelationRegistry
{
    /** @var array<class-string, list<class-string|RelationGroup|RelationManagerConfiguration>> */
    private static array $relations = [];

    /**
     * @param  class-string  $resourceClass
     * @param  list<class-string|RelationGroup|RelationManagerConfiguration>  $relationManagers
     */
    public static function register(string $resourceClass, array $relationManagers): void
    {
        self::$relations[$resourceClass] = array_merge(
            self::$relations[$resourceClass] ?? [],
            $relationManagers,
        );
    }

    /**
     * @param  class-string  $resourceClass
     * @return list<class-string|RelationGroup|RelationManagerConfiguration>
     */
    public static function for(string $resourceClass): array
    {
        return self::$relations[$resourceClass] ?? [];
    }

    public static function clear(): void
    {
        self::$relations = [];
    }
}
