<?php

namespace Moox\Restore\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Jobs\Traits\JobProgress;
use Moox\Restore\Events\RestoreFailedEvent;
use Moox\Restore\Models\RestoreBackup;

class ReplaceDomainInSqlFileJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    protected $restoreBackup;

    protected string $sqlFilePath;

    protected string $oldDomain;

    protected string $newDomain;

    public function __construct(int $restoreBackupId, $sqlFilePath)
    {
        $this->tries = 3;
        $this->timeout = 300;
        $this->maxExceptions = 1;
        $this->backoff = 350;

        $this->restoreBackup = RestoreBackup::find($restoreBackupId);
        $this->sqlFilePath = $sqlFilePath.'/'.config('restore.sql_file_name');
        $this->oldDomain = preg_quote(config('restore.old_domain')) ?? '';
        $this->newDomain = preg_quote(config('restore.new_domain')) ?? '';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->sqlFilePath || ! $this->oldDomain || ! $this->newDomain) {
            return;
        }
        try {
            $command = "sed -i 's/".$this->oldDomain.'/'.$this->newDomain."/g' ".$this->sqlFilePath;
            $output = shell_exec($command);
            if ($output === false) {
                RestoreFailedEvent::dispatch($this->restoreBackup->id, 'Failed to execute command: '.$command);
                throw new Exception('Failed to execute command: '.$command);
            }
        } catch (\Exception $e) {
            RestoreFailedEvent::dispatch($this->restoreBackup->id, $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
}
