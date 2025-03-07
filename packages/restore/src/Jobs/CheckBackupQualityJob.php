<?php

namespace Moox\Restore\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Moox\Jobs\Traits\JobProgress;

class CheckBackupQualityJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    protected string $backupPath;

    public function __construct(string $backupPath)
    {
        $this->backupPath = $backupPath;
    }

    public function handle()
    {
        $dumpFilePath = $this->backupPath.'dump.sql';
        $dumpFileContent = File::get($dumpFilePath);

        if (strpos($dumpFileContent, '--dump completed') === false) {
            throw new Exception('Backup is not completed or the dump file is corrupted.');
        }
    }
}
