<?php

declare(strict_types=1);

namespace Moox\EBilling\Delivery;

use Moox\EBilling\Contracts\DeliveryChannelInterface;
use Moox\EBilling\Contracts\DeliveryRecipientResolverInterface;
use Moox\EBilling\Contracts\InvoiceMailSenderInterface;
use Moox\EBilling\Data\DeliveryOutcome;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Orchestrator channel: resolve recipients, call the host mail sender, map outcomes.
 * Not a transport — register only via config('e-billing.delivery.channels').
 */
final class MailDeliveryChannel implements DeliveryChannelInterface
{
    public const FAILURE_NO_RECIPIENT = 'no_recipient';

    public function __construct(
        private readonly DeliveryRecipientResolverInterface $recipients,
        private readonly InvoiceMailSenderInterface $sender,
    ) {
    }

    public function key(): string
    {
        return 'mail';
    }

    /**
     * @return list<DeliveryOutcome>
     */
    public function deliver(EbillingDocument $document): array
    {
        $resolved = $this->recipients->resolve($document);

        if ($resolved === []) {
            return [
                new DeliveryOutcome(
                    recipient: '',
                    success: false,
                    failureReason: self::FAILURE_NO_RECIPIENT,
                    correlationId: null,
                ),
            ];
        }

        $outcomes = [];

        foreach ($resolved as $recipient) {
            $result = $this->sender->send($document, $recipient);

            $outcomes[] = new DeliveryOutcome(
                recipient: $result->actualRecipient ?? $recipient->address,
                success: $result->accepted,
                failureReason: $result->accepted ? null : ($result->failureReason ?? 'send_not_accepted'),
                correlationId: $result->correlationId,
            );
        }

        return $outcomes;
    }
}
