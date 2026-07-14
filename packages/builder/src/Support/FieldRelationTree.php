<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

final class FieldRelationTree
{
    /**
     * Eager-loads the full field tree for a field group (root fields + descendants).
     *
     * @return array<string, mixed>
     */
    public static function eagerLoadForDefinition(int $maxDepth = 4): array
    {
        $relations = [
            'translations',
            'fields' => fn ($query) => $query->whereNull('parent_field_id')->orderBy('sort'),
            'fields.translations',
            'fields.options',
            'fields.options.translations',
        ];

        $path = 'fields';

        for ($depth = 1; $depth <= $maxDepth; $depth++) {
            $path .= '.children';
            $relations[$path] = fn ($query) => $query->orderBy('sort');
            $relations["{$path}.translations"] = fn ($query) => $query;
            $relations["{$path}.options"] = fn ($query) => $query->orderBy('sort');
            $relations["{$path}.options.translations"] = fn ($query) => $query;
        }

        return $relations;
    }
}
