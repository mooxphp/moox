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

    /**
     * Merge form-submitted compound values with already stored ones.
     *
     * Nested fields hidden in $context are taken from $existing (never from
     * the request). Visible nested fields use $submitted. This prevents
     * crafted payloads from writing admin-hidden nested keys while keeping
     * those values intact across form saves.
     *
     * @param  Collection<int, FieldDefinition>|null  $children  Override for clone fields
     */
    public static function mergePreservingHidden(
        FieldDefinition $field,
        mixed $submitted,
        mixed $existing,
        string $context,
        ?Collection $children = null,
    ): mixed {
        $children ??= $field->children;

        if ($children->isEmpty() || ! is_array($submitted)) {
            return $submitted;
        }

        $existing = is_array($existing) ? $existing : [];

        return match ($field->type) {
            'repeater' => self::mergePreservingHiddenRepeaterRows($children, $submitted, $existing, $context),
            'flexible_content' => self::mergePreservingHiddenFlexibleItems($children, $submitted, $existing, $context),
            default => self::mergePreservingHiddenCompoundRow($children, $submitted, $existing, $context),
        };
    }

    /**
     * @param  Collection<int, FieldDefinition>  $children
     * @param  array<string, mixed>  $submitted
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    protected static function mergePreservingHiddenCompoundRow(
        Collection $children,
        array $submitted,
        array $existing,
        string $context,
    ): array {
        $merged = [];

        foreach ($children as $child) {
            if (! $child->isVisibleIn($context)) {
                if (array_key_exists($child->name, $existing)) {
                    $merged[$child->name] = $existing[$child->name];
                }

                continue;
            }

            if (! array_key_exists($child->name, $submitted)) {
                $merged[$child->name] = null;

                continue;
            }

            $merged[$child->name] = self::mergePreservingHidden(
                $child,
                $submitted[$child->name],
                $existing[$child->name] ?? null,
                $context,
            );
        }

        return $merged;
    }

    /**
     * @param  Collection<int, FieldDefinition>  $children
     * @param  array<int, mixed>  $submitted
     * @param  array<int, mixed>  $existing
     * @return list<array<string, mixed>>
     */
    protected static function mergePreservingHiddenRepeaterRows(
        Collection $children,
        array $submitted,
        array $existing,
        string $context,
    ): array {
        $merged = [];

        foreach (array_values($submitted) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $existingRow = is_array($existing[$index] ?? null) ? $existing[$index] : [];
            $merged[] = self::mergePreservingHiddenCompoundRow($children, $row, $existingRow, $context);
        }

        return $merged;
    }

    /**
     * @param  Collection<int, FieldDefinition>  $layouts
     * @param  array<int, mixed>  $submitted
     * @param  array<int, mixed>  $existing
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    protected static function mergePreservingHiddenFlexibleItems(
        Collection $layouts,
        array $submitted,
        array $existing,
        string $context,
    ): array {
        $layoutsByName = $layouts->keyBy('name');
        $merged = [];

        foreach (array_values($submitted) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = (string) ($item['type'] ?? '');
            $data = is_array($item['data'] ?? null) ? $item['data'] : [];
            $existingItem = is_array($existing[$index] ?? null) ? $existing[$index] : [];
            $existingData = is_array($existingItem['data'] ?? null) ? $existingItem['data'] : [];
            $layout = $layoutsByName->get($type);

            $merged[] = [
                'type' => $type,
                'data' => $layout instanceof FieldDefinition
                    ? self::mergePreservingHiddenCompoundRow($layout->children, $data, $existingData, $context)
                    : $data,
            ];
        }

        return $merged;
    }
}
