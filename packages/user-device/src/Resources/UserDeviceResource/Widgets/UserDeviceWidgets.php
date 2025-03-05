<?php

namespace Moox\UserDevice\Resources\UserDeviceResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\UserDevice\Models\UserDevice;
use Override;

class UserDeviceWidgets extends BaseWidget
{
    #[Override]
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = UserDevice::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('core::device.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::device.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::device.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
