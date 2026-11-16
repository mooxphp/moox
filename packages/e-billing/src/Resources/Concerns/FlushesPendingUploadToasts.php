<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\Concerns;

use Moox\EBilling\Support\PendingUploadToasts;

/**
 * Shows the uploader's pending toasts ({@see PendingUploadToasts}) on every Livewire request of the page, so a
 * background result appears while the user is still on the list (table polling) or on the next visit.
 */
trait FlushesPendingUploadToasts
{
    public function bootedFlushesPendingUploadToasts(): void
    {
        app(PendingUploadToasts::class)->flushForCurrentUser();
    }
}
