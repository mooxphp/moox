<?php

namespace Moox\Restore\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Moox\Restore\Models\RestoreBackup;
use Moox\Restore\Models\RestoreDestination;

class ProcessRestoreDestinationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $destination;

    /**
     * Create a new job instance.
     */
    public function __construct(RestoreDestination $destination)
    {
        $this->destination = $destination;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $latestBackup = $this->destination->source->backups()->orderBy('completed_at', 'desc')->first();

        if ($latestBackup) {
            $newRestoreBackup = new RestoreBackup;
            $newRestoreBackup->backup_id = $latestBackup->id;
            $newRestoreBackup->restore_destination_id = $this->destination->id;
            $newRestoreBackup->save();

            // Call the restore process
            Artisan::call('moox-restore:restore', ['restoreBackup' => $newRestoreBackup->id]);
        }
    }
}
