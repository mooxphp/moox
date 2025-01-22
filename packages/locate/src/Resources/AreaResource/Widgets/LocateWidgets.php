<?php

namespace Moox\Locate\Resources\LocateResource\Widgets;

use Override;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Locate\Models\Area;

class LocateWidgets extends BaseWidget
{
    #[Override]
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Area::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('locate::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('locate::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('locate::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
