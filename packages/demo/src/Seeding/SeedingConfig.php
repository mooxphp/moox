<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

final class SeedingConfig
{
    public static function resolveCount(string $packageSlug, int $default): int
    {
        if (! DemoAssetGate::enabled()) {
            return $default;
        }

        $datasetCount = config('demo.dataset_count');

        if (is_numeric($datasetCount)) {
            return max(1, (int) $datasetCount);
        }

        return $default;
    }
}

