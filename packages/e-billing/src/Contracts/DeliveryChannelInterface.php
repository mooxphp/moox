<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\DeliveryOutcome;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Host-bound delivery channel. The package ships no transport — hosts list
 * implementing classes in config('e-billing.delivery.channels').
 */
interface DeliveryChannelInterface
{
    /**
     * Stable channel id stored on delivery attempt rows (e.g. "mail").
     */
    public function key(): string;

    /**
     * Deliver the approved document. Each outcome becomes one attempt record.
     *
     * @return list<DeliveryOutcome>
     */
    public function deliver(EbillingDocument $document): array;
}
