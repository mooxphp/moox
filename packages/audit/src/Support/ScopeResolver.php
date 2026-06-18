<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

final class ScopeResolver
{
    public static function forModel(Model $model): ?string
    {
        if (self::hasScopeColumn($model)) {
            $scope = $model->getAttribute('scope');

            return is_string($scope) && $scope !== '' ? $scope : null;
        }

        if ($model instanceof BaseDraftTranslationModel) {
            $main = self::resolveDraftTranslationParent($model);

            if ($main instanceof Model && self::hasScopeColumn($main)) {
                $scope = $main->getAttribute('scope');

                return is_string($scope) && $scope !== '' ? $scope : null;
            }
        }

        return null;
    }

    private static function resolveDraftTranslationParent(BaseDraftTranslationModel $model): ?Model
    {
        $foreignKey = str_replace('_translations', '_id', $model->getTable());
        $parentId = $model->getAttribute($foreignKey);

        if (! filled($parentId)) {
            return null;
        }

        $parentClass = str_replace('Translation', '', $model::class);

        if (! class_exists($parentClass)) {
            return null;
        }

        return $parentClass::query()->find($parentId);
    }

    private static function hasScopeColumn(Model $model): bool
    {
        return array_key_exists('scope', $model->getAttributes())
            || in_array('scope', $model->getFillable(), true);
    }
}
