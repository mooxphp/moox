<?php

namespace Moox\Restore\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\Jobs\Traits\JobProgress;
use Moox\Restore\Events\RestoreCompletedEvent;
use Moox\Restore\Events\RestoreFailedEvent;
use Moox\Restore\Models\RestoreBackup;

class ImportDatabaseJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    protected $restoreBackup;

    protected string $sqlFilePath;

    public function __construct(int $restoreBackupId, string $sqlFilePath)
    {
        $this->tries = 1;
        $this->maxExceptions = 1;
        $this->backoff = 350;

        $this->restoreBackup = RestoreBackup::find($restoreBackupId);

        $this->sqlFilePath = $sqlFilePath.'/'.config('restore.sql_file_name');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (! $this->sqlFilePath) {
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception('Database import failed. Sql file not found'.$this->sqlFilePath));
                throw new Exception('Database import failed. Sql file not found'.$this->sqlFilePath);
            }

            $destinationEnvData = $this->restoreBackup->restoreDestination->env_data;
            $command = sprintf(
                'mysql  -u %s -p%s %s < %s > /dev/null 2>&1',
                $destinationEnvData['DB_USERNAME'],
                $destinationEnvData['DB_PASSWORD'],
                $destinationEnvData['DB_DATABASE'],
                $this->sqlFilePath
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                if (config('restore.debug_mode')) {
                    Log::info('Database import output: '.implode("\n", $output));
                }

                $errorMessage = 'Database import failed. Return code: ';

                if (config('restore.debug_mode')) {
                    Log::error($errorMessage);
                }

                RestoreFailedEvent::dispatch($this->restoreBackup->id, $errorMessage);
                throw new Exception($errorMessage);
            }

            if (config('restore.debug_mode')) {
                Log::info($command);
            }
            RestoreCompletedEvent::dispatch($this->restoreBackup->id);
        } catch (\Exception $e) {
            RestoreFailedEvent::dispatch($this->restoreBackup->id, $e);
            throw new Exception($e->getMessage());
        }
    }
}
