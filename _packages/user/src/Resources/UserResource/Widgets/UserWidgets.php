<?php

namespace Moox\User\Resources\UserResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\User\Models\User;

class UserWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = User::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('user::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('user::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('user::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
