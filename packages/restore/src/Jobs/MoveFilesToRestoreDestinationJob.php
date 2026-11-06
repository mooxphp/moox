<?php

namespace Moox\Restore\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Moox\Jobs\Traits\JobProgress;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Moox\Restore\Models\RestoreBackup;
use Symfony\Component\Process\Process;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Moox\Restore\Events\RestoreFailedEvent;
use Moox\Restore\Events\RestoreStartedEvent;
use Symfony\Component\Filesystem\Filesystem;

class MoveFilesToRestoreDestinationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels, Batchable;

    public $backoff;

    protected $restoreBackup;


    public function __construct(int $restoreBackupId)
    {
        $this->backoff = 3000;

        $this->restoreBackup = RestoreBackup::find($restoreBackupId);
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $backup = $this->restoreBackup->backup;
            $destination = $this->restoreBackup->restoreDestination;


            $sourcePath = storage_path('app') . '/private' . '/' . $backup->path . $backup->source->host; //to use the source.some.url.test not the timestamp folder
            $destinationPath = str_replace(env('BACKUP_HOST'), $destination->host, base_path());

            if (config('restore.debug_mode')) {
                Log::info("Source path: " . $sourcePath);
                Log::info("Destination path: " . $destinationPath);
            }

            if (!is_dir($sourcePath)) {
                $errorMessage = "Source path does not exist: " . $sourcePath;
                if (config('restore.debug_mode')) {
                    Log::error($errorMessage);
                }
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception("Sourcepath is not a path " . $destinationPath));
                return;
            }

            if (!is_dir($destinationPath)) {
                $errorMessage = "Destination path does not exist: " . $destinationPath;
                if (config('restore.debug_mode')) {
                    Log::error($errorMessage);
                }
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception("Destinationpath is not a path " . $destinationPath));
                return;
            }

            $command = sprintf("cp -r %s/* %s > /dev/null 2>&1", escapeshellarg($sourcePath), escapeshellarg($destinationPath));

            if (config('restore.debug_mode')) {
                Log::info("Executing command: " . $command);
            }

            $filesystem = new Filesystem();
            $filesystem->mirror($sourcePath, $destinationPath);
        } catch (\Exception $e) {
            if (config('restore.debug_mode')) {
                Log::error('MoveFilesToRestoreDestinationJob failed for backup ID: ' . $this->restoreBackup->id . ' with error: ' . $e->getMessage());
            }
            RestoreFailedEvent::dispatch($this->restoreBackup->id, $e);
            throw new Exception($e->getMessage());
        }
    }
}
