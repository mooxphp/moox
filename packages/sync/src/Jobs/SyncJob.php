<?php

namespace Moox\Sync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Sync\Models\Sync;

class SyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sync;

    protected $modelData;

    protected $eventType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Sync $sync, array $modelData, string $eventType)
    {
        $this->sync = $sync;
        $this->modelData = $modelData;
        $this->eventType = $eventType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $targetModelClass = $this->sync->target_model;

        switch ($this->eventType) {
            case 'created':
                $this->createOrUpdateRecord($targetModelClass);
                break;

            case 'updated':
                $this->createOrUpdateRecord($targetModelClass);
                break;

            case 'deleted':
                $this->deleteRecord($targetModelClass);
                break;
        }
    }

    protected function createOrUpdateRecord($targetModelClass)
    {
        $targetModel = $targetModelClass::updateOrCreate(
            ['id' => $this->modelData['id']],
            $this->modelData
        );
    }

    protected function deleteRecord($targetModelClass)
    {
        $targetModel = $targetModelClass::find($this->modelData['id']);
        if ($targetModel) {
            $targetModel->delete();
        }
    }
}
