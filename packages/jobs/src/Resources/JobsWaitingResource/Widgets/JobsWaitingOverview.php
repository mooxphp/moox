<?php

namespace Moox\Jobs\Resources\JobsWaitingResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Jobs\Models\Job;
use Moox\Jobs\Models\JobManager;
use Moox\Jobs\Traits\FormatSeconds;

class JobsWaitingOverview extends BaseWidget
{
    use FormatSeconds;

    protected function getCards(): array
    {
        $jobsWaiting = Job::query()
            ->select(DB::raw('COUNT(*) as count'))
            ->first();

        $aggregationColumns = [
            DB::raw('SUM(finished_at - started_at) as total_time_elapsed'),
            DB::raw('AVG(finished_at - started_at) as average_time_elapsed'),
        ];

        $aggregatedInfo = JobManager::query()
            ->select($aggregationColumns)
            ->first();

        if ($aggregatedInfo) {
            $averageTime = property_exists($aggregatedInfo, 'average_time_elapsed') ? ceil((float) $aggregatedInfo->average_time_elapsed).'s' : '0';
            $totalTime = property_exists($aggregatedInfo, 'total_time_elapsed') ? $this->formatSeconds($aggregatedInfo->total_time_elapsed).'s' : '0';
        } else {
            $averageTime = '0';
            $totalTime = '0';
        }

        return [
            Stat::make(__('jobs::translations.waiting_jobs'), $jobsWaiting->count ?? 0),
            Stat::make(__('jobs::translations.execution_time'), $totalTime),
            Stat::make(__('jobs::translations.average_time'), $averageTime),
        ];
    }
}
