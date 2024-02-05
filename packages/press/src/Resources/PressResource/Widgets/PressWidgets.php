<?php

namespace Moox\Press\Resources\PressResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Press\Models\Press;

class PressWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Press::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('press::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('press::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('press::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
