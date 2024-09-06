<?php

namespace Moox\Sync\Handlers;

use Illuminate\Support\Facades\DB;
use Moox\Core\Traits\LogLevel;

abstract class AbstractSyncHandler
{
    use LogLevel;

    protected $modelClass;

    protected $modelData;

    protected $eventType;

    public function __construct(string $modelClass, array $modelData, string $eventType)
    {
        $this->modelClass = $modelClass;
        $this->modelData = $modelData;
        $this->eventType = $eventType;
    }

    public function handle()
    {
        DB::beginTransaction();

        try {
            $this->logInfo('Moox Sync: Starting sync process', [
                'model_class' => $this->modelClass,
                'event_type' => $this->eventType,
            ]);

            if ($this->eventType === 'deleted') {
                $this->deleteModel();
            } else {
                $this->syncModel();
            }

            DB::commit();
            $this->logInfo('Moox Sync: Sync process completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logDebug('Moox Sync: Sync failed: '.$e->getMessage(), [
                'model_class' => $this->modelClass,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    abstract protected function syncModel();

    abstract protected function deleteModel();
}
