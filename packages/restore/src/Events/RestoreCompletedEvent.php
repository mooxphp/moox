<?php

namespace Moox\Restore\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RestoreCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $restoreBackupId;

    public function __construct($restoreBackupId)
    {
        $this->restoreBackupId = $restoreBackupId;
    }
}
