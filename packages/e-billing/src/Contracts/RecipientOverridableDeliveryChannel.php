<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\DeliveryOutcome;
use Moox\EBilling\Data\DeliveryRecipient;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Optional for a {@see DeliveryChannelInterface}: the channel can deliver to recipients the operator
 * chose during selective redispatch instead of the resolved ones (recipient override, ADR 0016).
 * Channels without it ignore an override.
 */
interface RecipientOverridableDeliveryChannel
{
    /**
     * @param  list<DeliveryRecipient>  $recipients
     * @return list<DeliveryOutcome>
     */
    public function deliverTo(EbillingDocument $document, array $recipients): array;
}
