<?php

namespace Moox\Sync\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ModelCompatibilityChecker
{
    /**
     * Check if the source and target models are compatible.
     */
    public static function areModelsCompatible(string $sourceModelClass, string $targetModelClass): bool
    {
        $sourceModel = new $sourceModelClass;
        $targetModel = new $targetModelClass;

        if (! $sourceModel instanceof Model || ! $targetModel instanceof Model) {
            return false;
        }

        $sourceColumns = Schema::getColumnListing($sourceModel->getTable());
        $targetColumns = Schema::getColumnListing($targetModel->getTable());

        return empty(array_diff($sourceColumns, $targetColumns));
    }

    public static function checkCompatibility(string $sourceModelClass, string $targetModelClass): array
    {
        $sourceModel = new $sourceModelClass;
        $targetModel = new $targetModelClass;

        if (! $sourceModel instanceof Model || ! $targetModel instanceof Model) {
            return [
                'compatible' => false,
                'error' => 'One or both models are not valid Eloquent models.',
                'missingColumns' => [],
                'extraColumns' => [],
            ];
        }

        $sourceColumns = Schema::getColumnListing($sourceModel->getTable());
        $targetColumns = Schema::getColumnListing($targetModel->getTable());

        $missingColumns = array_diff($sourceColumns, $targetColumns);
        $extraColumns = array_diff($targetColumns, $sourceColumns);

        return [
            'compatible' => empty($missingColumns),
            'error' => empty($missingColumns) ? null : 'Target model is missing some columns from the source model.',
            'missingColumns' => $missingColumns,
            'extraColumns' => $extraColumns,
        ];
    }
}
