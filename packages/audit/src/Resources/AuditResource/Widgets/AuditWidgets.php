<?php

namespace Moox\Audit\Resources\AuditResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Audit\Models\ActivityLog;

class AuditWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = ActivityLog::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('audit::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('audit::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('audit::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
