<?php

namespace Moox\Permission\Resources\PermissionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Permission\Models\Permission;
use Override;

class PermissionWidgets extends BaseWidget
{
    #[Override]
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Permission::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('permission::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('permission::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('permission::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
