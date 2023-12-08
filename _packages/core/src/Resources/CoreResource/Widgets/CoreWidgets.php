<?php

namespace Moox\Core\Resources\CoreResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Core\Models\Core;

class CoreWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Core::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('core::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
