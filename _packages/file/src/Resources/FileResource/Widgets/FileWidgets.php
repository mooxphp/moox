<?php

namespace Moox\File\Resources\FileResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\File\Models\File;

class FileWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = File::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('file::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('file::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('file::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
