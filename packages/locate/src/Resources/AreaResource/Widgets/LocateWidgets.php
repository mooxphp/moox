<?php

namespace Moox\Locate\Resources\LocateResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Locate\Models\Locate;

class LocateWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Locate::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('locate::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('locate::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('locate::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
