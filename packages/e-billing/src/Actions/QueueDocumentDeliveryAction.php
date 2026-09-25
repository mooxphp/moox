<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use InvalidArgumentException;
use Moox\EBilling\Jobs\DispatchDocumentJob;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Queues delivery when config e-billing.delivery.enabled is true.
 */
final class QueueDocumentDeliveryAction
{
    /**
     * @param  array<int, mixed>|null  $channelKeys  null = every configured channel
     */
    public function execute(EbillingDocument $document, ?array $channelKeys = null): void
    {
        if (! (bool) config('e-billing.delivery.enabled', false)) {
            return;
        }

        $normalized = $this->normalizeChannelKeys($channelKeys);

        DispatchDocumentJob::dispatch((string) $document->getKey(), $normalized);
    }

    /**
     * @param  array<int, mixed>|null  $channelKeys
     * @return list<string>|null
     */
    private function normalizeChannelKeys(?array $channelKeys): ?array
    {
        if ($channelKeys === null) {
            return null;
        }

        if ($channelKeys === []) {
            throw new InvalidArgumentException('At least one delivery channel key is required.');
        }

        $normalized = [];

        foreach ($channelKeys as $key) {
            if (! is_string($key) || $key === '') {
                throw new InvalidArgumentException('Each delivery channel key must be a non-empty string.');
            }

            $normalized[] = $key;
        }

        return $normalized;
    }
}
