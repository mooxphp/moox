<?php

declare(strict_types=1);

namespace Moox\Core\Relations;

use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationPerspective;
use Moox\Core\Relations\Enums\RelationPresentation;

final class RelationConfigNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function normalize(string $key, array $raw): array
    {
        $config = RelationRegistry::merge($raw);

        if (isset($config['owner_types']) && ! isset($config['perspective'])) {
            $config['perspective'] = RelationPerspective::Related->value;
        }

        if (! isset($config['kind'])) {
            $config['kind'] = self::inferKind($config);
        }

        if (! isset($config['perspective'])) {
            $config['perspective'] = RelationPerspective::Owner->value;
        }

        if (! isset($config['presentation'])) {
            $config['presentation'] = RelationPresentation::Tab->value;
        }

        if (! isset($config['relationship'])) {
            $config['relationship'] = $key;
        }

        if (isset($config['model']) && ! isset($config['related_model'])) {
            $config['related_model'] = $config['model'];
        }

        if (isset($config['table']) && ! isset($config['pivot_table'])) {
            $config['pivot_table'] = $config['table'];
        }

        if (isset($config['morph_name']) && ! isset($config['morph_type'])) {
            $config['morph_type'] = $config['morph_name'];
        }

        if (isset($config['pivot_columns']) && ! isset($config['pivot_attributes'])) {
            $config['pivot_attributes'] = $config['pivot_columns'];
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $morphRelations
     * @return array<string, array<string, mixed>>
     */
    public static function fromMorphRelations(array $morphRelations): array
    {
        $normalized = [];

        foreach ($morphRelations as $key => $config) {
            if (! is_array($config)) {
                continue;
            }

            $normalized[$key] = self::normalize((string) $key, array_replace($config, [
                'kind' => RelationKind::MorphPivot->value,
                'perspective' => RelationPerspective::Owner->value,
                'presentation' => RelationPresentation::Tab->value,
            ]));
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $taxonomies
     * @return array<string, array<string, mixed>>
     */
    public static function fromTaxonomies(array $taxonomies): array
    {
        $normalized = [];

        foreach ($taxonomies as $key => $config) {
            if (! is_array($config)) {
                continue;
            }

            $normalized[$key] = self::normalize((string) $key, array_replace($config, [
                'kind' => RelationKind::MorphPivot->value,
                'perspective' => RelationPerspective::Owner->value,
                'presentation' => RelationPresentation::Inline->value,
                'related_model' => $config['model'] ?? null,
                'pivot_table' => $config['table'] ?? null,
                'morph_type' => $config['relationship'] ?? $key,
                'foreign_key' => $config['foreignKey'] ?? null,
                'related_key' => $config['relatedKey'] ?? null,
            ]));
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function inferKind(array $config): string
    {
        if (isset($config['owner_types']) || ($config['perspective'] ?? null) === RelationPerspective::Related->value) {
            return RelationKind::PivotHasMany->value;
        }

        if (isset($config['inverse_relationship'], $config['pivot_table'])) {
            return RelationKind::BelongsToMany->value;
        }

        if (isset($config['pivot_table'], $config['morph_type']) || isset($config['pivot_table'], $config['morph_name'])) {
            return RelationKind::MorphPivot->value;
        }

        if (($config['relationship'] ?? '') === 'parent') {
            return RelationKind::BelongsTo->value;
        }

        if (isset($config['children']) || ($config['relationship'] ?? '') === 'children') {
            return RelationKind::HasMany->value;
        }

        return RelationKind::MorphPivot->value;
    }
}
