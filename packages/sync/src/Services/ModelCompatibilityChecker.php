<?php

namespace Moox\Sync\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ModelCompatibilityChecker
{
    /**
     * Check if the source and target models are compatible.
     *
     * @param string $sourceModelClass
     * @param string $targetModelClass
     * @return bool
     */
    public static function areModelsCompatible(string $sourceModelClass, string $targetModelClass): bool
    {
        $sourceModel = new $sourceModelClass;
        $targetModel = new $targetModelClass;

        if (!$sourceModel instanceof Model || !$targetModel instanceof Model) {
            return false;
        }

        $sourceColumns = Schema::getColumnListing($sourceModel->getTable());
        $targetColumns = Schema::getColumnListing($targetModel->getTable());

        return empty(array_diff($sourceColumns, $targetColumns));
    }
}
