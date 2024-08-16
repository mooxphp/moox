<?php

namespace Moox\UserSession\Resources\UserSessionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\UserSession\Models\UserSession;

class UserSessionWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = UserSession::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('core::session.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::session.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::session.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
