<?php

declare(strict_types=1);

namespace Moox\Core\Relations;

use Moox\Core\Relations\Enums\RelationTabAction;

final class RelationTabActionRegistry
{
    /**
     * @return list<string>
     */
    public static function headerActions(ResolvedRelation $relation): array
    {
        return self::actionsFor($relation, 'header');
    }

    /**
     * @return list<string>
     */
    public static function recordActions(ResolvedRelation $relation): array
    {
        return self::actionsFor($relation, 'record');
    }

    /**
     * @return list<string>
     */
    public static function toolbarActions(ResolvedRelation $relation): array
    {
        return self::actionsFor($relation, 'toolbar');
    }

    public static function hasHeaderAction(ResolvedRelation $relation, RelationTabAction $action): bool
    {
        return in_array($action->value, self::headerActions($relation), true);
    }

    public static function hasRecordAction(ResolvedRelation $relation, RelationTabAction $action): bool
    {
        return in_array($action->value, self::recordActions($relation), true);
    }

    public static function hasToolbarAction(ResolvedRelation $relation, RelationTabAction $action): bool
    {
        return in_array($action->value, self::toolbarActions($relation), true);
    }

    /**
     * @return array<string, mixed>
     */
    public static function actionOptions(ResolvedRelation $relation, RelationTabAction $action): array
    {
        $kindBlueprint = RelationRegistry::blueprintDefinition($relation->kind->value);
        $fromBlueprint = $kindBlueprint[$action->value] ?? [];
        $fromRelation = $relation->config[$action->value] ?? [];
        $fromActions = $relation->config['actions'][$action->value] ?? [];

        if (! is_array($fromBlueprint)) {
            $fromBlueprint = [];
        }

        if (! is_array($fromRelation)) {
            $fromRelation = [];
        }

        if (! is_array($fromActions)) {
            $fromActions = [];
        }

        return array_replace_recursive($fromBlueprint, $fromRelation, $fromActions);
    }

    /**
     * @return list<string>
     */
    private static function actionsFor(ResolvedRelation $relation, string $placement): array
    {
        $configured = $relation->config['actions'][$placement] ?? null;

        if (is_array($configured)) {
            return self::normalizeList($configured);
        }

        $kindBlueprint = RelationRegistry::blueprintDefinition($relation->kind->value);
        $fromBlueprint = $kindBlueprint['actions'][$placement] ?? [];

        return self::normalizeList(is_array($fromBlueprint) ? $fromBlueprint : []);
    }

    /**
     * @return list<string>
     */
    private static function normalizeList(array $actions): array
    {
        return array_values(array_filter(
            array_map(strval(...), $actions),
            fn (string $action): bool => RelationTabAction::tryFrom($action) !== null,
        ));
    }
}
