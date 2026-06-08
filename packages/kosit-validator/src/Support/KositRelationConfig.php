<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

class KositRelationConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function kositValidatables(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('kosit-validator.relations.kosit_validatables', []);

        return $config;
    }

    public static function relationshipName(): string
    {
        return (string) (self::kositValidatables()['relationship'] ?? 'kositValidatables');
    }

    public static function pivotTable(): string
    {
        return (string) (self::kositValidatables()['pivot_table'] ?? 'kosit_validatables');
    }

    public static function morphName(): string
    {
        return (string) (self::kositValidatables()['morph_name'] ?? 'validatable');
    }

    /**
     * @return list<string>
     */
    public static function pivotColumns(): array
    {
        /** @var list<string> $columns */
        $columns = self::kositValidatables()['pivot_columns'] ?? [];

        return $columns;
    }

    /**
     * @return array<class-string, string>
     */
    public static function ownerTypes(): array
    {
        /** @var array<class-string, string> $types */
        $types = self::kositValidatables()['owner_types'] ?? [];

        return $types;
    }
}
