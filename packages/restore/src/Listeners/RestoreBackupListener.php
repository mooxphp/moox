<?php

namespace Moox\Restore\Listeners;

use Moox\Restore\Models\RestoreBackup;
use Moox\Restore\Events\RestoreFailedEvent;
use Moox\Restore\Events\RestoreStartedEvent;
use Moox\Restore\Events\RestoreCompletedEvent;

class RestoreBackupListener
{

    /**
     * Handle the event.
     */
    public function handle(RestoreCompletedEvent|RestoreFailedEvent|RestoreStartedEvent $event): void
    {
        $restoreBackup = RestoreBackup::find($event->restoreBackupId);

        if ($restoreBackup) {
            if ($event instanceof RestoreStartedEvent) {
                $restoreBackup->markAsInProgress();
            } elseif ($event instanceof RestoreCompletedEvent) {
                $restoreBackup->markAsCompleted();
            } elseif ($event instanceof RestoreFailedEvent) {
                $restoreBackup->markAsFailed($event->errorMessage);
            }
        }
    }
}
