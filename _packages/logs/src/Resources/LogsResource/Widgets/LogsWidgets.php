<?php

namespace Moox\Logs\Resources\LogsResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Logs\Models\Logs;

class LogsWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Logs::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('logs::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('logs::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('logs::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
