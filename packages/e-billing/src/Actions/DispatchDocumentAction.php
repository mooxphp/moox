<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use InvalidArgumentException;
use Moox\EBilling\Approval\DocumentDispatchGuard;
use Moox\EBilling\Contracts\DeliveryChannelInterface;
use Moox\EBilling\Models\EbillingDocument;

final class DispatchDocumentAction
{
    public function __construct(
        private readonly DocumentDispatchGuard $dispatchGuard,
        private readonly RecordDeliveryAttemptsAction $recordAttempts,
    ) {
    }

    /**
     * Run bound delivery channels for an approved document.
     * When delivery is disabled, asserts the gate then returns without writing records.
     */
    public function execute(EbillingDocument $document): void
    {
        $this->dispatchGuard->assertDispatchable($document);

        if (! (bool) config('e-billing.delivery.enabled', false)) {
            return;
        }

        $channels = config('e-billing.delivery.channels', []);

        if (! is_array($channels)) {
            throw new InvalidArgumentException("config('e-billing.delivery.channels') must be an array of class names.");
        }

        foreach ($channels as $channelClass) {
            if (! is_string($channelClass) || $channelClass === '') {
                throw new InvalidArgumentException('Each delivery channel must be a non-empty class name.');
            }

            if (! is_a($channelClass, DeliveryChannelInterface::class, true)) {
                throw new InvalidArgumentException(
                    "Delivery channel must implement ".DeliveryChannelInterface::class.": {$channelClass}"
                );
            }

            /** @var DeliveryChannelInterface $channel */
            $channel = app($channelClass);
            $outcomes = $channel->deliver($document);
            $this->recordAttempts->execute($document, $channel->key(), $outcomes);
        }
    }
}
