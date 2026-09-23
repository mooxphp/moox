<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\DeliveryRecipient;
use Moox\EBilling\Delivery\MailDeliveryChannel;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Host-bound recipient list for {@see MailDeliveryChannel}.
 */
interface DeliveryRecipientResolverInterface
{
    /**
     * @return list<DeliveryRecipient>
     */
    public function resolve(EbillingDocument $document): array;
}
