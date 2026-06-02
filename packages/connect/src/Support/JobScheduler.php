<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Moox\Connect\Jobs\BaseSyncJob;
use RuntimeException;

final class JobScheduler
{
    private string $cachePrefix;

    private Schedule $schedule;

    public function __construct(
        Schedule $schedule,
        string $cachePrefix = 'job_schedule:'
    ) {
        $this->schedule = $schedule;
        $this->cachePrefix = $cachePrefix;
    }

    public function scheduleJob(
        BaseSyncJob $job,
        string $frequency,
        ?array $options = null
    ): void {
        $this->validateFrequency($frequency);

        $scheduleId = $this->generateScheduleId($job);
        $this->storeSchedule($scheduleId, [
            'job' => serialize($job),
            'frequency' => $frequency,
            'options' => $options,
            'created_at' => Carbon::now(),
            'last_run' => null,
            'next_run' => null,
            'active' => true,
        ]);

        $this->registerSchedule($scheduleId, $job, $frequency, $options);
    }

    public function unscheduleJob(BaseSyncJob $job): void
    {
        $scheduleId = $this->generateScheduleId($job);
        $this->removeSchedule($scheduleId);
    }

    public function pauseJob(BaseSyncJob $job): void
    {
        $scheduleId = $this->generateScheduleId($job);
        $this->updateScheduleStatus($scheduleId, false);
    }

    public function resumeJob(BaseSyncJob $job): void
    {
        $scheduleId = $this->generateScheduleId($job);
        $this->updateScheduleStatus($scheduleId, true);
    }

    public function getSchedule(BaseSyncJob $job): ?array
    {
        $scheduleId = $this->generateScheduleId($job);

        return $this->getScheduleData($scheduleId);
    }

    private function registerSchedule(
        string $scheduleId,
        BaseSyncJob $job,
        string $frequency,
        ?array $options
    ): void {
        $event = $this->schedule->job($job);

        match ($frequency) {
            'everyMinute' => $event->everyMinute(),
            'everyFiveMinutes' => $event->everyFiveMinutes(),
            'everyFifteenMinutes' => $event->everyFifteenMinutes(),
            'everyThirtyMinutes' => $event->everyThirtyMinutes(),
            'hourly' => $event->hourly(),
            'daily' => $event->daily(),
            'weekly' => $event->weekly(),
            'monthly' => $event->monthly(),
            'custom' => $this->applyCustomSchedule($event, $options),
            default => throw new RuntimeException("Unsupported frequency: {$frequency}")
        };

        if ($options !== null) {
            $this->applyOptions($event, $options);
        }

        $event->before(function () use ($scheduleId) {
            $this->updateLastRun($scheduleId);
        });

        $event->after(function () use ($scheduleId, $frequency, $options) {
            $this->updateNextRun($scheduleId, $frequency, $options);
        });
    }

    private function applyCustomSchedule($event, ?array $options): void
    {
        if (! isset($options['cron'])) {
            throw new RuntimeException('Custom schedule requires cron expression');
        }

        $event->cron($options['cron']);
    }

    private function applyOptions($event, array $options): void
    {
        if (isset($options['timezone'])) {
            $event->timezone($options['timezone']);
        }

        if (isset($options['environments'])) {
            $event->environments($options['environments']);
        }

        if (isset($options['evenInMaintenanceMode'])) {
            $event->evenInMaintenanceMode();
        }

        if (isset($options['withoutOverlapping'])) {
            $event->withoutOverlapping($options['withoutOverlapping']);
        }
    }

    private function generateScheduleId(BaseSyncJob $job): string
    {
        return md5(get_class($job).$job->getJobId());
    }

    private function storeSchedule(string $scheduleId, array $data): void
    {
        Cache::forever($this->getCacheKey($scheduleId), $data);
    }

    private function removeSchedule(string $scheduleId): void
    {
        Cache::forget($this->getCacheKey($scheduleId));
    }

    private function updateScheduleStatus(string $scheduleId, bool $active): void
    {
        $data = $this->getScheduleData($scheduleId);
        if ($data !== null) {
            $data['active'] = $active;
            $this->storeSchedule($scheduleId, $data);
        }
    }

    private function updateLastRun(string $scheduleId): void
    {
        $data = $this->getScheduleData($scheduleId);
        if ($data !== null) {
            $data['last_run'] = Carbon::now();
            $this->storeSchedule($scheduleId, $data);
        }
    }

    private function updateNextRun(
        string $scheduleId,
        string $frequency,
        ?array $options
    ): void {
        $data = $this->getScheduleData($scheduleId);
        if ($data !== null) {
            $data['next_run'] = $this->calculateNextRun($frequency, $options);
            $this->storeSchedule($scheduleId, $data);
        }
    }

    private function calculateNextRun(string $frequency, ?array $options): Carbon
    {
        $now = Carbon::now();

        return match ($frequency) {
            'everyMinute' => $now->addMinute(),
            'everyFiveMinutes' => $now->addMinutes(5),
            'everyFifteenMinutes' => $now->addMinutes(15),
            'everyThirtyMinutes' => $now->addMinutes(30),
            'hourly' => $now->addHour(),
            'daily' => $now->addDay(),
            'weekly' => $now->addWeek(),
            'monthly' => $now->addMonth(),
            'custom' => $this->calculateCustomNextRun($options['cron']),
            default => throw new RuntimeException("Unsupported frequency: {$frequency}")
        };
    }

    private function calculateCustomNextRun(string $expression): Carbon
    {
        // This is a simplified implementation
        // In production, you'd want to use a proper cron expression parser
        return Carbon::now()->addHour();
    }

    private function getScheduleData(string $scheduleId): ?array
    {
        return Cache::get($this->getCacheKey($scheduleId));
    }

    private function getCacheKey(string $scheduleId): string
    {
        return $this->cachePrefix.$scheduleId;
    }

    private function validateFrequency(string $frequency): void
    {
        $validFrequencies = [
            'everyMinute',
            'everyFiveMinutes',
            'everyFifteenMinutes',
            'everyThirtyMinutes',
            'hourly',
            'daily',
            'weekly',
            'monthly',
            'custom',
        ];

        if (! in_array($frequency, $validFrequencies, true)) {
            throw new RuntimeException("Invalid frequency: {$frequency}");
        }
    }
}
