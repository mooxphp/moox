<?php

namespace Moox\LoginLink\Resources\LoginLinkResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\LoginLink\Models\LoginLink;

class LoginLinkWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = LoginLink::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('login-link::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('login-link::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('login-link::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
