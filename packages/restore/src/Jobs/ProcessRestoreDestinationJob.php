<?php

namespace Moox\Restore\Jobs;


use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Moox\Restore\Models\RestoreBackup;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Moox\Restore\Models\RestoreDestination;

class ProcessRestoreDestinationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

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
            $newRestoreBackup = new RestoreBackup();
            $newRestoreBackup->backup_id = $latestBackup->id;
            $newRestoreBackup->restore_destination_id = $this->destination->id;
            $newRestoreBackup->save();

            // Call the restore process
            Artisan::call('moox-restore:restore', ['restoreBackup' => $newRestoreBackup->id]);
        }
    }
}
