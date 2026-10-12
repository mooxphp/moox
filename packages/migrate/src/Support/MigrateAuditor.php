<?php

declare(strict_types=1);

namespace Moox\Migrate\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Soft-depends on moox/audit: without it, migrate still runs, nothing is logged.
 */
final class MigrateAuditor
{
    public static function log(
        ?Model $causer,
        bool $ok,
        bool $busy,
        ?int $exitCode,
        int $pendingCount,
    ): void {
        $logger = 'Moox\\Audit\\Services\\MooxActivityLogger';

        if (! class_exists($logger)) {
            return;
        }

        if (! config('migrate.audit.enabled', true)) {
            return;
        }

        $status = $busy ? 'busy' : ($ok ? 'ok' : 'fail');
        $event = 'migrate_run';
        $logName = (string) config('migrate.audit.log_name', 'migrate');

        $options = [
            'event' => $event,
            'entry_type' => 'log',
            'properties' => [
                'ok' => $ok,
                'busy' => $busy,
                'status' => $status,
                'exit_code' => $exitCode,
                'pending_count' => $pendingCount,
            ],
        ];

        if ($causer instanceof Model) {
            $options['causer'] = $causer;
        }

        $logger::log($logName, $event, $options);
    }
}
