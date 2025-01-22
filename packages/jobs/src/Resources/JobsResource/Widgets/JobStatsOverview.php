<?php

namespace Moox\Jobs\Resources\JobsResource\Widgets;

use Override;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Jobs\Models\JobManager;
use Moox\Jobs\Traits\FormatSeconds;

class JobStatsOverview extends BaseWidget
{
    use FormatSeconds;

    #[Override]
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw($this->dbSum('finished_at', 'started_at').' as total_time_elapsed'),
            DB::raw($this->dbAvg('finished_at', 'started_at').' as average_time_elapsed'),
        ];

        $aggregatedInfo = JobManager::query()
            ->select($aggregationColumns)
            ->first();

        if (isset($aggregatedInfo->average_time_elapsed)) {
            $averageTime = ($aggregatedInfo->average_time_elapsed)
            ? ceil((float) $aggregatedInfo->average_time_elapsed).'s' : '0';
        } else {
            $averageTime = '0';
        }

        if (isset($aggregatedInfo->total_time_elapsed)) {
            $totalTime = ($aggregatedInfo->total_time_elapsed)
            ? $this->formatSeconds($aggregatedInfo->total_time_elapsed) : '0';
        } else {
            $totalTime = '0';
        }

        return [
            Stat::make(__('jobs::translations.total_jobs'), $aggregatedInfo->count ?? 0),
            Stat::make(__('jobs::translations.execution_time'), $totalTime),
            Stat::make(__('jobs::translations.average_time'), $averageTime),
        ];
    }

    private function dbAvg(string $col1, string $col2): string
    {
        return 'AVG('.$this->dbColumnAsInteger($col1).' - '.$this->dbColumnAsInteger($col2).')'.(DB::connection()->getConfig()['driver'] === 'pgsql' ? '::int' : '');
    }

    private function dbSum(string $col1, string $col2): string
    {
        return 'SUM('.$this->dbColumnAsInteger($col1).' - '.$this->dbColumnAsInteger($col2).')'.(DB::connection()->getConfig()['driver'] === 'pgsql' ? '::int' : '');
    }

    private function dbColumnAsInteger(string $colName): string
    {
        if (DB::connection()->getConfig()['driver'] === 'pgsql') {
            return 'cast(extract(epoch from '.$colName.') as integer)';
        }

        return $colName;
    }
}
