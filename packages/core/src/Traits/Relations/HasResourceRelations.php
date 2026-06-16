<?php

declare(strict_types=1);

namespace Moox\Core\Traits\Relations;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Moox\Core\Filament\RelationManagers\ConfigRelationManager;
use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationPerspective;
use Moox\Core\Relations\Enums\RelationPresentation;
use Moox\Core\Relations\ResolvedRelation;

trait HasResourceRelations
{
    use HasInlineRelationFields;

    /**
     * Resource-specific relation managers not covered by configuration.
     *
     * @return array<class-string|RelationGroup|RelationManagerConfiguration>
     */
    protected static function getDeclaredRelations(): array
    {
        return [];
    }

    /**
     * @return array<class-string|RelationGroup|RelationManagerConfiguration>
     */
    public static function getRelations(): array
    {
        return array_merge(
            static::getDeclaredRelations(),
            static::buildConfiguredRelationManagers(),
        );
    }

    /**
     * @return array<RelationGroup>
     */
    protected static function buildConfiguredRelationManagers(): array
    {
        if (! method_exists(static::getModel(), 'getResourceName')) {
            return [];
        }

        $service = static::relationServiceFor(static::getResourceName());
        $managers = [];

        foreach ($service->tabRelations() as $key => $relation) {
            if ($relation->presentation !== RelationPresentation::Tab) {
                continue;
            }

            if (! static::supportsConfiguredRelationManager($relation)) {
                continue;
            }

            $managers[] = RelationGroup::make(
                $relation->label(),
                [
                    ConfigRelationManager::make([
                        'relationKey' => $key,
                    ]),
                ],
            );
        }

        return $managers;
    }

    protected static function hasConfiguredRelation(string $key): bool
    {
        if (! method_exists(static::getModel(), 'getResourceName')) {
            return false;
        }

        return static::relationServiceFor(static::getResourceName())->has($key);
    }

    protected static function supportsConfiguredRelationManager(ResolvedRelation $relation): bool
    {
        return match ($relation->kind) {
            RelationKind::MorphPivot => $relation->perspective === RelationPerspective::Owner,
            RelationKind::PivotHasMany => $relation->perspective === RelationPerspective::Related,
            RelationKind::BelongsToMany => true,
            RelationKind::BelongsTo => true,
            RelationKind::HasMany => true,
            default => false,
        };
    }
}
