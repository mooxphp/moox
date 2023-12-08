<?php

namespace Moox\Data\Resources\DataResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Data\Models\Data;

class DataWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Data::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('data::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('data::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('data::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
