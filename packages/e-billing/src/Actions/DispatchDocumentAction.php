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
     *
     * @param  array<int, mixed>|null  $channelKeys  null = every configured channel; non-empty subset otherwise
     */
    public function execute(EbillingDocument $document, ?array $channelKeys = null): void
    {
        $this->dispatchGuard->assertDispatchable($document);

        if (! (bool) config('e-billing.delivery.enabled', false)) {
            return;
        }

        if ($channelKeys !== null && $channelKeys === []) {
            throw new InvalidArgumentException('At least one delivery channel key is required.');
        }

        $channels = config('e-billing.delivery.channels', []);

        if (! is_array($channels)) {
            throw new InvalidArgumentException("config('e-billing.delivery.channels') must be an array of class names.");
        }

        /** @var list<DeliveryChannelInterface> $instances */
        $instances = [];

        foreach ($channels as $channelClass) {
            if (! is_string($channelClass) || $channelClass === '') {
                throw new InvalidArgumentException('Each delivery channel must be a non-empty class name.');
            }

            if (! is_a($channelClass, DeliveryChannelInterface::class, true)) {
                throw new InvalidArgumentException(
                    'Delivery channel must implement '.DeliveryChannelInterface::class.': '.$channelClass
                );
            }

            /** @var DeliveryChannelInterface $channel */
            $channel = app($channelClass);
            $instances[] = $channel;
        }

        $selected = $this->resolveSelectedChannels($instances, $channelKeys);

        foreach ($selected as $channel) {
            $outcomes = $channel->deliver($document);
            $this->recordAttempts->execute($document, $channel->key(), $outcomes);
        }
    }

    /**
     * @param  list<DeliveryChannelInterface>  $instances
     * @param  array<int, mixed>|null  $channelKeys
     * @return list<DeliveryChannelInterface>
     */
    private function resolveSelectedChannels(array $instances, ?array $channelKeys): array
    {
        if ($channelKeys === null) {
            return $instances;
        }

        $requested = [];

        foreach ($channelKeys as $key) {
            if (! is_string($key) || $key === '') {
                throw new InvalidArgumentException('Each delivery channel key must be a non-empty string.');
            }

            $requested[$key] = true;
        }

        $selected = [];

        foreach ($instances as $channel) {
            $key = $channel->key();

            if (isset($requested[$key])) {
                $selected[] = $channel;
                unset($requested[$key]);
            }
        }

        if ($requested !== []) {
            throw new InvalidArgumentException(
                'Unknown or unconfigured delivery channel key(s): '.implode(', ', array_keys($requested))
            );
        }

        return $selected;
    }
}
