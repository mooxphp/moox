<?php

namespace Moox\Restore\Jobs;

use Exception;

use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Moox\Jobs\Traits\JobProgress;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;

class CheckBackupQualityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels, Batchable;

    protected string $backupPath;

    public function __construct(string $backupPath)
    {
        $this->backupPath = $backupPath;
    }

    public function handle()
    {
        $dumpFilePath = $this->backupPath . 'dump.sql';
        $dumpFileContent = File::get($dumpFilePath);

        if (strpos($dumpFileContent, '--dump completed') === false) {
            throw new Exception("Backup is not completed or the dump file is corrupted.");
        }
    }
}
