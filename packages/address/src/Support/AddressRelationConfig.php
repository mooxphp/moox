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
     * @return array<class-string, array{label: string, title_attribute: ?string}>
     */
    public static function ownerTypeDefinitions(): array
    {
        /** @var array<class-string, string|array{label?: string, title_attribute?: string}> $raw */
        $raw = self::addressables()['owner_types'] ?? [];

        $definitions = [];

        foreach ($raw as $class => $definition) {
            if (! is_string($class) || $class === '') {
                continue;
            }

            if (is_string($definition)) {
                $definitions[$class] = [
                    'label' => $definition,
                    'title_attribute' => null,
                ];

                continue;
            }

            if (! is_array($definition)) {
                continue;
            }

            $definitions[$class] = [
                'label' => (string) ($definition['label'] ?? class_basename($class)),
                'title_attribute' => isset($definition['title_attribute']) && is_string($definition['title_attribute']) && $definition['title_attribute'] !== ''
                    ? $definition['title_attribute']
                    : null,
            ];
        }

        return $definitions;
    }

    /**
     * @return array<class-string, string>
     */
    public static function ownerTypes(): array
    {
        $labels = [];

        foreach (self::ownerTypeDefinitions() as $class => $definition) {
            $labels[$class] = $definition['label'];
        }

        return $labels;
    }

    public static function titleAttributeForOwnerType(string $class): ?string
    {
        return self::ownerTypeDefinitions()[$class]['title_attribute'] ?? null;
    }
}
