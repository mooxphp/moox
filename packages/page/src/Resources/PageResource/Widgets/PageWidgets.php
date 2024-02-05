<?php

namespace Moox\Page\Resources\PageResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Page\Models\Page;

class PageWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Page::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('page::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('page::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('page::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
