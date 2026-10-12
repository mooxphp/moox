<?php

declare(strict_types=1);

namespace Moox\Migrate\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class MigrateRunner
{
    public const LOCK_KEY = 'moox-migrate-run';

    public const LOCK_SECONDS = 600;

    /**
     * @return array{ok: bool, busy: bool, title: string, body: ?string}
     */
    public function run(): array
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_SECONDS);

        if (! $lock->get()) {
            return [
                'ok' => false,
                'busy' => true,
                'title' => __('migrate::migrate.migrate_busy'),
                'body' => null,
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
                ];
            }

            Log::error('moox migrate failed', ['exit_code' => $exitCode, 'output' => $output]);

            return [
                'ok' => false,
                'busy' => false,
                'title' => __('migrate::migrate.migrate_failed'),
                'body' => MigrateOutput::forNotification($output),
            ];
        } catch (Throwable $exception) {
            Log::error('moox migrate failed', ['exception' => $exception]);

            return [
                'ok' => false,
                'busy' => false,
                'title' => __('migrate::migrate.migrate_failed'),
                'body' => MigrateOutput::forNotification($exception->getMessage()),
            ];
        } finally {
            $lock->release();
        }
    }
}
