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
use Illuminate\Support\Facades\Log;
use Moox\Jobs\Traits\JobProgress;
use Moox\Restore\Events\RestoreFailedEvent;
use Moox\Restore\Models\RestoreBackup;

class RewriteDotEnvJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

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
            $restoreDestination = $this->restoreBackup->restoreDestination;
            $data = $this->restoreBackup->restoreDestination->env_data;
            $envPath = str_replace(env('BACKUP_HOST'), $restoreDestination->host, base_path()).'/.env';

            $contents = File::get($envPath);

            if (! $contents) {
                RestoreFailedEvent::dispatch($this->restoreBackup->id, new Exception('File .env not found!'));
                throw new Exception('File .env not found!');
            }
            $lines = explode("\n", $contents);

            foreach ($lines as &$line) {
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                $parts = explode('=', $line, 2);
                $key = $parts[0];

                if (array_key_exists($key, $data)) {
                    $line = $key.'='.$data[$key];
                    unset($data[$key]);
                }
            }

            // Append any new keys that were not present in the original file
            foreach ($data as $key => $value) {
                $lines[] = $key.'='.$value;
                if (config('restore.debug_mode')) {
                    Log::info('added .env key: '.$key.'='.$value);
                }
            }

            $updatedContents = implode("\n", $lines);

            File::put($envPath, $updatedContents);
        } catch (\Exception $e) {
            RestoreFailedEvent::dispatch($this->restoreBackup->id, $e);
            throw new Exception($e->getMessage());
        }
    }
}
