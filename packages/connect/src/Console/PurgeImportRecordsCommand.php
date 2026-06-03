<?php

declare(strict_types=1);

namespace Moox\Connect\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Models\ApiImportRecord;

final class PurgeImportRecordsCommand extends Command
{
    protected $signature = 'connect:purge-import-records {--endpoint= : Optional endpoint id} {--dry-run : Only show counts}';

    protected $description = 'Force delete soft-deleted api_import_records per endpoint retention.';

    public function handle(): int
    {
        $endpointId = $this->option('endpoint');
        $dryRun = (bool) $this->option('dry-run');

        $endpoints = ApiEndpoint::query()
            ->when($endpointId, fn ($q) => $q->whereKey($endpointId))
            ->get(['id', 'name', 'options']);

        if ($endpoints->isEmpty()) {
            $this->info('No endpoints with purge_after_days configured.');

            return self::SUCCESS;
        }

        $configured = 0;

        foreach ($endpoints as $endpoint) {
            /** @var ApiEndpoint $endpoint */
            $days = (int) ($endpoint->option('sync.purge_after_days', $endpoint->purge_after_days) ?? 0);
            if ($days <= 0) {
                continue;
            }
            $configured++;

            $cutoff = Carbon::now()->subDays($days);

            $q = ApiImportRecord::onlyTrashed()
                ->where('api_endpoint_id', $endpoint->id)
                ->where('deleted_at', '<', $cutoff);

            $count = (clone $q)->count();
            $this->line(sprintf(
                'Endpoint #%d (%s): purge_after_days=%d, candidates=%d',
                $endpoint->id,
                (string) ($endpoint->name ?? ''),
                $days,
                $count
            ));

            if ($dryRun || $count === 0) {
                continue;
            }

            // chunked force-delete to avoid huge single queries
            $q->orderBy('id')->chunkById(1000, function ($records): void {
                foreach ($records as $record) {
                    /** @var ApiImportRecord $record */
                    $record->forceDelete();
                }
            });
        }

        if ($configured === 0) {
            $this->info('No endpoints with purge_after_days configured.');
        }

        return self::SUCCESS;
    }
}
