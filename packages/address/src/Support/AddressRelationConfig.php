<?php

declare(strict_types=1);

namespace Moox\Address\Support;

class AddressRelationConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function addressables(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('address.relations.addressables', []);

        return $config;
    }

    public static function relationshipName(): string
    {
        return (string) (self::addressables()['relationship'] ?? 'addressables');
    }

    public static function pivotTable(): string
    {
        return (string) (self::addressables()['pivot_table'] ?? 'addressables');
    }

    /**
     * @return list<string>
     */
    public static function pivotColumns(): array
    {
        /** @var list<string> $columns */
        $columns = self::addressables()['pivot_columns'] ?? [];

        return $columns;
    }

    /**
     * @return array<class-string, string>
     */
    public static function ownerTypes(): array
    {
        /** @var array<class-string, string> $types */
        $types = self::addressables()['owner_types'] ?? [];

        return $types;
    }
}
