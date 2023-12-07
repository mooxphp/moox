<?php

namespace Adrolli\FilamentJobManager\Resources\JobsResource\Widgets;

use Adrolli\FilamentJobManager\Models\JobManager;
use Adrolli\FilamentJobManager\Traits\FormatSeconds;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class JobStatsOverview extends BaseWidget
{
    use FormatSeconds;

    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(finished_at - started_at) as total_time_elapsed'),
            DB::raw('AVG(finished_at - started_at) as average_time_elapsed'),
        ];

        $aggregatedInfo = JobManager::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('filament-job-manager::translations.total_jobs'), $aggregatedInfo->count ?? 0),
            Stat::make(__('filament-job-manager::translations.execution_time'), ($this->formatSeconds($aggregatedInfo->total_time_elapsed ?? 0)?? '0 s')),
            Stat::make(__('filament-job-manager::translations.average_time'), ceil((float) $aggregatedInfo->average_time_elapsed).'s' ?? 0),
        ];
    }
}
