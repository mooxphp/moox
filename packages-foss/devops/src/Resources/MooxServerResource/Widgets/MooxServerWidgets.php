<?php

namespace Moox\Devops\Resources\MooxServerResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Devops\Models\MooxServer;

class MooxServerWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = MooxServer::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('devops::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('devops::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('devops::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
