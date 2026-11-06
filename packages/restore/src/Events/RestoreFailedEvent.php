<?php

namespace Moox\Restore\Events;

use Exception;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestoreFailedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $restoreBackupId;
    public $errorMessage;

    public function __construct($restoreBackupId, $e)
    {
        $this->restoreBackupId = $restoreBackupId;
        if ($e instanceof Exception) {
            $this->errorMessage = $e->getMessage();
        } else {
            $this->errorMessage = $e;
        }
    }
}
