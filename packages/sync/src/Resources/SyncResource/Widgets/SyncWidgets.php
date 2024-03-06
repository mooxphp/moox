<?php

namespace Moox\Sync\Resources\SyncResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Sync\Models\Sync;

class SyncWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Sync::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('sync::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('sync::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('sync::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
