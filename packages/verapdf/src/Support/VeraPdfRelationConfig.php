<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Support;

class VeraPdfRelationConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function veraPdfValidatables(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('verapdf.relations.verapdf_validatables', []);

        return $config;
    }

    public static function relationshipName(): string
    {
        return (string) (self::veraPdfValidatables()['relationship'] ?? 'veraPdfValidatables');
    }

    public static function pivotTable(): string
    {
        return (string) (self::veraPdfValidatables()['pivot_table'] ?? 'verapdf_validatables');
    }

    public static function morphName(): string
    {
        return (string) (self::veraPdfValidatables()['morph_name'] ?? 'validatable');
    }

    /**
     * @return list<string>
     */
    public static function pivotColumns(): array
    {
        /** @var list<string> $columns */
        $columns = self::veraPdfValidatables()['pivot_columns'] ?? [];

        return $columns;
    }

    /**
     * @return array<class-string, string>
     */
    public static function ownerTypes(): array
    {
        /** @var array<class-string, string> $types */
        $types = self::veraPdfValidatables()['owner_types'] ?? [];

        return $types;
    }
}
