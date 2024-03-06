<?php

namespace Moox\Builder\Resources\BuilderResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Builder\Models\Item;

class BuilderWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Item::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('builder::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('builder::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('builder::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
