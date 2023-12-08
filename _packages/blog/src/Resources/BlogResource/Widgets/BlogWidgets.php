<?php

namespace Moox\Blog\Resources\BlogResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Blog\Models\Blog;

class BlogWidgets extends BaseWidget
{
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Blog::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('blog::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('blog::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('blog::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
