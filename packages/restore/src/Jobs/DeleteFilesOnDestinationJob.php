<?php

namespace Moox\Restore\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Moox\Jobs\Traits\JobProgress;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Moox\Restore\Models\RestoreBackup;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Moox\Restore\Events\RestoreFailedEvent;
use Moox\Restore\Events\RestoreStartedEvent;

class DeleteFilesOnDestinationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels, Batchable;


    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    protected $restoreBackup;


    public function __construct(int $restoreBackupId)
    {
        $this->tries = 3;
        $this->timeout = 300;
        $this->maxExceptions = 1;
        $this->backoff = 350;

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

            RestoreStartedEvent::dispatch($this->restoreBackup->id);

            $sourcePath = storage_path('app') . '/private' . '/' . $backup->path . $backup->source->host; //to use the source.some.url.test not the timestamp folder
            $destinationPath = str_replace(env('BACKUP_HOST'), $destination->host, base_path());

            if (!is_dir($sourcePath)) {
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception("Sourcepath is not a path " . $sourcePath));
                throw new Exception("Sourcepath is not a path " . $sourcePath);
            }

            if (!is_dir($destinationPath)) {
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception("Destinationpath is not a path " . $destinationPath));
                throw new Exception("Destinationpath is not a path " . $destinationPath);
            }

            $this->setProgress(10);

            $deleteFilesCommand = sprintf('rm -rf %s', escapeshellarg($destinationPath)); // will also delete hidden files
            exec($deleteFilesCommand, $outputDeleteFiles, $returnDeleteVar);

            if ($returnDeleteVar !== 0) {
                if (config('restore.debug_mode')) {
                    Log::error("Error while deleting files " . implode("\n", $outputDeleteFiles));
                }
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception("Error while deleting files"));
                throw new Exception("Error while deleting files");
            }

            $createFolderCommand = sprintf('mkdir %s', escapeshellarg($destinationPath));
            if (config('restore.debug_mode')) {
                Log::info($createFolderCommand);
            }
            exec($createFolderCommand, $outPutCreateFolder, $returnCreateVar);

            if ($returnCreateVar !== 0) {
                if (config('restore.debug_mode')) {
                    Log::error("Error while create folder " . implode("\n", $outputDeleteFiles));
                }
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception("Error while create folde"));
                throw new Exception("Error while create folde");
            }

            $destinationIsEmpty = !file_exists($destinationPath) || count(scandir($destinationPath)) == 2; // 2 because of . and ..

            if ($destinationIsEmpty) {
                if (config('restore.debug_mode')) {
                    Log::info("Destination path is empty: " . $destinationPath);
                }
            } else {
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception("Destination path is not empty: " . $destinationPath));
                throw new Exception("Destination path is not empty: " . $destinationPath);
            }

            $this->setProgress(100);
        } catch (\Exception $e) {
            RestoreFailedEvent::dispatch($this->restoreBackup->id, $e);
            throw new Exception($e->getMessage());
        }
    }
}
