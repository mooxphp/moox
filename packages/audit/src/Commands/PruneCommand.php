<?php

declare(strict_types=1);

namespace Moox\Audit\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PruneCommand extends Command
{
    protected $signature = 'mooxaudit:prune
        {--dry-run : Show affected rows without deleting them}';

    protected $description = 'Prunes old audit and log entries using audit.retention.';

    public function handle(): int
    {
        $activityModel = config('audit.activity_model');

        if (! is_string($activityModel) || ! class_exists($activityModel) || ! is_subclass_of($activityModel, Model::class)) {
            $this->error('Invalid audit.activity_model configuration.');

            return self::FAILURE;
        }

        $retention = config('audit.retention', []);

        if (! is_array($retention) || $retention === []) {
            $this->warn('No audit.retention rules configured.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach ($retention as $entryType => $days) {
            if (! is_string($entryType) || $entryType === '') {
                continue;
            }

            if ($days === null) {
                $this->line(sprintf('Skipping [%s]: keep indefinitely.', $entryType));

                continue;
            }

            if (! is_int($days) && ! is_numeric($days)) {
                $this->warn(sprintf('Skipping [%s]: retention must be an integer day count or null.', $entryType));

                continue;
            }

            $days = (int) $days;

            if ($days < 0) {
                $this->warn(sprintf('Skipping [%s]: retention cannot be negative.', $entryType));

                continue;
            }

            $cutoff = Carbon::now()->subDays($days);

            /** @var class-string<Model> $activityModel */
            $query = $activityModel::query()
                ->where('entry_type', $entryType)
                ->where('created_at', '<', $cutoff);

            $affected = $query->count();

            $message = sprintf(
                '%s [%s] entries older than %s days (before %s).',
                $dryRun ? 'Would prune' : 'Pruned',
                $entryType,
                $days,
                $cutoff->toDateTimeString(),
            );

            if (! $dryRun && $affected > 0) {
                $query->delete();
            }

            $total += $affected;
            $this->line($message.' Count: '.$affected);
        }

        $this->info(($dryRun ? 'Dry run complete.' : 'Prune complete.').' Total affected: '.$total);

        return self::SUCCESS;
    }
}
