<?php

declare(strict_types=1);

namespace Moox\Migrate\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Moox\Migrate\Jobs\RunMigrationsJob;
use Throwable;

final class MigrateRunner
{
    public const LOCK_KEY = 'moox-migrate-run';

    public const LOCK_SECONDS = 600;

    /**
     * Queue a migrate run for the Filament UI.
     *
     * @return array{ok: bool, busy: bool, queued: bool, terminal: bool, title: string, body: ?string}
     */
    public function queue(?Model $causer = null): array
    {
        if (MigrateRunStatus::isActive()) {
            return [
                'ok' => false,
                'busy' => true,
                'queued' => false,
                'terminal' => true,
                'title' => __('migrate::migrate.migrate_busy'),
                'body' => null,
            ];
        }

        $pendingCount = app(MigrationInventory::class)->pendingCount();
        $userId = $causer?->getKey();
        $userType = $causer instanceof Model ? $causer::class : null;

        MigrateRunStatus::put([
            'state' => MigrateRunStatus::STATE_QUEUED,
            'title' => __('migrate::migrate.migrate_queued'),
            'body' => null,
            'exit_code' => null,
            'pending_count' => $pendingCount,
            'user_id' => $userId,
            'user_type' => $userType,
        ]);

        RunMigrationsJob::dispatch($userId, $userType, $pendingCount);

        $status = MigrateRunStatus::get();

        if ($status !== null && in_array($status['state'], [
            MigrateRunStatus::STATE_DONE,
            MigrateRunStatus::STATE_FAILED,
            MigrateRunStatus::STATE_BUSY,
        ], true)) {
            return [
                'ok' => $status['state'] === MigrateRunStatus::STATE_DONE,
                'busy' => $status['state'] === MigrateRunStatus::STATE_BUSY,
                'queued' => false,
                'terminal' => true,
                'title' => $status['title'],
                'body' => $status['body'] ?? null,
            ];
        }

        return [
            'ok' => true,
            'busy' => false,
            'queued' => true,
            'terminal' => false,
            'title' => __('migrate::migrate.migrate_queued'),
            'body' => null,
        ];
    }

    /**
     * Run migrate under the cache lock (used by the queue job).
     *
     * @return array{ok: bool, busy: bool, title: string, body: ?string, exit_code: ?int}
     */
    public function execute(): array
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);

        if (! $lock->get()) {
            return [
                'ok' => false,
                'busy' => true,
                'title' => __('migrate::migrate.migrate_busy'),
                'body' => null,
                'exit_code' => null,
            ];
        }

        try {
            set_time_limit(0);

            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                Log::info('moox migrate completed', ['output' => $output]);

                return [
                    'ok' => true,
                    'busy' => false,
                    'title' => __('migrate::migrate.migrate_done'),
                    'body' => MigrateOutput::forNotification($output),
                    'exit_code' => $exitCode,
                ];
            }

            Log::error('moox migrate failed', ['exit_code' => $exitCode, 'output' => $output]);

            return [
                'ok' => false,
                'busy' => false,
                'title' => __('migrate::migrate.migrate_failed'),
                'body' => MigrateOutput::forNotification($output),
                'exit_code' => $exitCode,
            ];
        } catch (Throwable $exception) {
            Log::error('moox migrate failed', ['exception' => $exception]);

            return [
                'ok' => false,
                'busy' => false,
                'title' => __('migrate::migrate.migrate_failed'),
                'body' => MigrateOutput::forNotification($exception->getMessage()),
                'exit_code' => null,
            ];
        } finally {
            $lock->release();
        }
    }
}
