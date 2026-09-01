<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\Concerns;

use Moox\EBilling\Support\IdenticalDuplicateNotifier;

trait FlushesIdenticalDuplicateToast
{
    public function bootedFlushesIdenticalDuplicateToast(): void
    {
        $this->checkIdenticalDuplicateToast();
    }

    public function checkIdenticalDuplicateToast(): void
    {
        app(IdenticalDuplicateNotifier::class)->flushPendingToastForCurrentUser();
    }
}
