<?php

namespace Moox\Security\Resources\ResetPasswordResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Security\Models\ResetPassword;
use Moox\Security\Models\Security;

class ResetPasswordWidgets extends BaseWidget
{
    //    protected function getCards(): array
    //    {
    //        $aggregationColumns = [
    //            DB::raw('COUNT(*) as count'),
    //            DB::raw('COUNT(*) as count'),
    //            DB::raw('COUNT(*) as count'),
    //        ];
    //
    //        $aggregatedInfo = ResetPassword::query()
    //            ->select($aggregationColumns)
    //            ->first();
    //
    //        return [
    //            Stat::make(__('core::security.totalone'), $aggregatedInfo->count ?? 0),
    //            Stat::make(__('core::security.totaltwo'), $aggregatedInfo->count ?? 0),
    //            Stat::make(__('core::security.totalthree'), $aggregatedInfo->count ?? 0),
    //        ];
    //    }
}
