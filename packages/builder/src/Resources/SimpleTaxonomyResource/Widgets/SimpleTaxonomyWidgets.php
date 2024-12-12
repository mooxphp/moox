<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleTaxonomyResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Builder\Models\SimpleTaxonomy;

class SimpleTaxonomyWidgets extends BaseWidget
{
    protected function getStats(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(CASE WHEN publish_at <= NOW() THEN 1 END) as published_count'),
            DB::raw('COUNT(CASE WHEN publish_at > NOW() THEN 1 END) as scheduled_count'),
            DB::raw('COUNT(CASE WHEN publish_at IS NULL THEN 1 END) as draft_count'),
        ];

        $aggregatedInfo = SimpleTaxonomy::select($aggregationColumns)->first();

        return [
            Stat::make(__('core::core.published'), $aggregatedInfo['published_count']),
            Stat::make(__('core::core.scheduled'), $aggregatedInfo['scheduled_count']),
            Stat::make(__('core::core.drafted'), $aggregatedInfo['draft_count']),
        ];
    }
}
