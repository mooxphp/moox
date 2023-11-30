<?php

namespace Moox\Skeleton\Resources\SkeletonResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Skeleton\Models\Skeleton;

class SkeletonWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Skeleton::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('skeleton::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('skeleton::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('skeleton::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
