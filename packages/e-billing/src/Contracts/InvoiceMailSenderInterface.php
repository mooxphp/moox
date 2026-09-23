<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\DeliveryRecipient;
use Moox\EBilling\Data\InvoiceMailSendResult;
use Moox\EBilling\Delivery\MailDeliveryChannel;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Host-bound mail transport for {@see MailDeliveryChannel}.
 * Implementations may use mail-outbox, Laravel Mail, or another stack.
 */
interface InvoiceMailSenderInterface
{
    public function send(EbillingDocument $document, DeliveryRecipient $recipient): InvoiceMailSendResult;
}
