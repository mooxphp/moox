<?php

namespace Moox\MooxPressWiki\Resources\MooxPressWikiResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\MooxPressWiki\Models\WpWiki;

class MooxPressWikiWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = WpWiki::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('core::moox-press-wiki.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::moox-press-wiki.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::moox-press-wiki.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
