<?php

declare(strict_types=1);

namespace Moox\Core\Traits\MorphPivot;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Moox\Core\Filament\RelationManagers\MorphPivotRelationManager;
use Moox\Core\Support\MorphPivot\MorphPivotRelationRegistry;

trait HasResourceMorphPivotRelations
{
    /**
     * Resource-specific relation managers (children, …).
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
            static::buildMorphPivotRelationManagers(),
        );
    }

    /**
     * @return array<RelationGroup>
     */
    protected static function buildMorphPivotRelationManagers(): array
    {
        if (! method_exists(static::getModel(), 'getResourceName')) {
            return [];
        }

        $resourceName = static::getModel()::getResourceName();
        $relations = config("{$resourceName}.morph_relations", []);

        $managers = [];

        foreach ($relations as $configKey => $config) {
            if (! is_array($config)) {
                continue;
            }

            $config = MorphPivotRelationRegistry::mergeConfig($config);
            $model = $config['model'] ?? null;

            if (! is_string($model) || $model === '' || ! class_exists($model)) {
                continue;
            }

            $label = $config['label'] ?? $configKey;
            $relationshipName = $config['relationship'] ?? $configKey;
            $relatedResource = $config['related_resource'] ?? null;

            $managers[] = RelationGroup::make(
                is_string($label) ? $label : (string) $configKey,
                [
                    MorphPivotRelationManager::make([
                        'morphRelationConfigKey' => (string) $configKey,
                        'relationshipName' => is_string($relationshipName) ? $relationshipName : (string) $configKey,
                        'relatedResourceClass' => is_string($relatedResource) && $relatedResource !== ''
                            ? $relatedResource
                            : null,
                    ]),
                ],
            );
        }

        return $managers;
    }
}
