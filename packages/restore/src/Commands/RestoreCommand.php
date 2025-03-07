<?php

declare(strict_types=1);

namespace Moox\Restore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Moox\Restore\Jobs\DeleteFilesOnDestinationJob;
use Moox\Restore\Jobs\ImportDatabaseJob;
use Moox\Restore\Jobs\MoveFilesToRestoreDestinationJob;
use Moox\Restore\Jobs\ReplaceDomainInSqlFileJob;
use Moox\Restore\Jobs\RewriteDotEnvJob;
use Moox\Restore\Models\RestoreBackup;

class RestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moox-restore:restore {restoreBackup}';

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
        $restoreBackup = RestoreBackup::findOrFail($this->argument('restoreBackup'));
        $sqlFilePath = str_replace(config('restore.backup_host'), $restoreBackup->restoreDestination->host, base_path()).config('sql_file_name');
        if (config('restore.debug_mode')) {
            Log::info($sqlFilePath);
        }
        if ($restoreBackup) {
            Bus::batch([
                [
                    new DeleteFilesOnDestinationJob($restoreBackup->id),
                    new MoveFilesToRestoreDestinationJob($restoreBackup->id),
                    new RewriteDotEnvJob($restoreBackup->id),
                    new ReplaceDomainInSqlFileJob($restoreBackup->id, $sqlFilePath),
                    new ImportDatabaseJob($restoreBackup->id, $sqlFilePath),
                ],
            ])
                ->onConnection(config('restore.queue_connection'))
                ->onQueue(config('restore.queue'))
                ->name('Restore Backup '.$restoreBackup->restoreDestination->host)
                ->then(function () use ($restoreBackup) {
                    return $restoreBackup;
                })
                ->finally(function () use ($restoreBackup) {
                    return $restoreBackup;
                })
                ->dispatch();
        }
    }
}
