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
            'fields' => fn ($query) => $query->whereNull('parent_field_id')->orderBy('sort'),
            'fields.options',
        ];

        $path = 'fields';

        for ($depth = 1; $depth <= $maxDepth; $depth++) {
            $path .= '.children';
            $relations[$path] = fn ($query) => $query->orderBy('sort');
            $relations["{$path}.options"] = fn ($query) => $query->orderBy('sort');
        }

        return $relations;
    }
}
