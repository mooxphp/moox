<?php

declare(strict_types=1);

namespace Moox\Restore\Commands;

use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Moox\Restore\Models\RestoreDestination;
use Moox\Restore\Jobs\ProcessRestoreDestinationJob;
use Spatie\BackupServer\Tasks\Summary\Actions\CreateServerSummaryAction;

class  DispatchRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moox-restore:dispatch-restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Restoring Backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $summary = [];

        $restoreDestinations = RestoreDestination::all();

        $jobs = $restoreDestinations->map(function ($destination) {
            return new ProcessRestoreDestinationJob($destination);
        });

        Bus::batch($jobs)
            ->then(function (Batch $batch) use ($summary) {})
            ->catch(function (Batch $batch, \Throwable $e) use ($summary) {
                Log::error('Restore batch failed.', ['error' => $e->getMessage()]);
            })
            ->finally(function (Batch $batch) {
                Log::info('completed');
            })
            ->dispatch();
    }
}
