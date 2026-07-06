<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Collection;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;

/**
 * Visibility contexts for field groups and fields. Settings are stored per
 * context as `visible_{context}` booleans and default to visible when absent,
 * so existing groups/fields stay visible everywhere without a migration.
 *
 * A group cascades to its fields: hiding a group in a context removes all of
 * its fields from that context.
 */
final class FieldVisibility
{
    public const ADMIN = 'admin';

    public const FRONTEND = 'frontend';

    public const API = 'api';

    /** @var list<string> */
    public const CONTEXTS = [self::ADMIN, self::FRONTEND, self::API];

    /**
     * Keep only the groups (and, cascading, the fields) visible in the given context.
     *
     * @param  Collection<int, FieldGroupDefinition>  $groups
     * @return Collection<int, FieldGroupDefinition>
     */
    public static function filterGroups(Collection $groups, string $context): Collection
    {
        return $groups
            ->filter(fn (FieldGroupDefinition $group): bool => $group->isVisibleIn($context))
            ->map(fn (FieldGroupDefinition $group): FieldGroupDefinition => $group->withFields(
                self::filterFields($group->fields, $context),
            ))
            ->values();
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return Collection<int, FieldDefinition>
     */
    public static function filterFields(Collection $fields, string $context): Collection
    {
        return $fields
            ->filter(fn (FieldDefinition $field): bool => $field->isVisibleIn($context))
            ->map(fn (FieldDefinition $field): FieldDefinition => $field->children->isEmpty()
                ? $field
                : $field->withChildren(self::filterFields($field->children, $context)))
            ->values();
    }
}
