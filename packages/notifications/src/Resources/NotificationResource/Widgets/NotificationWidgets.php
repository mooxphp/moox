<?php

namespace Moox\Notification\Resources\NotificationResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Notification\Models\Notification;

class NotificationWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Notification::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('core::sync.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::sync.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::sync.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
