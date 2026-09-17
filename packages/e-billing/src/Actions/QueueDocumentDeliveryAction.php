<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Jobs\DispatchDocumentJob;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Queues delivery when config e-billing.delivery.enabled is true.
 */
final class QueueDocumentDeliveryAction
{
    public function execute(EbillingDocument $document): void
    {
        if (! (bool) config('e-billing.delivery.enabled', false)) {
            return;
        }

        DispatchDocumentJob::dispatch((string) $document->getKey());
    }
}
