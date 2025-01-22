<?php

namespace Moox\Sync\Handlers;

use Exception;
use Illuminate\Support\Facades\DB;
use Moox\Core\Traits\LogLevel;

abstract class AbstractSyncHandler
{
    use LogLevel;

    public function __construct(protected string $modelClass, protected array $modelData, protected string $eventType)
    {
    }

    public function handle(): void
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
        } catch (Exception $exception) {
            DB::rollBack();
            $this->logDebug('Moox Sync: Sync failed: '.$exception->getMessage(), [
                'model_class' => $this->modelClass,
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }

    abstract protected function syncModel();

    abstract protected function deleteModel();
}
