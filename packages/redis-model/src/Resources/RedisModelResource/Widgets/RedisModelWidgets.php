<?php

namespace Moox\RedisModel\Resources\RedisModelResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\RedisModel\Models\RedisModel;

class RedisModelWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = RedisModel::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('redis-model::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('redis-model::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('redis-model::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
