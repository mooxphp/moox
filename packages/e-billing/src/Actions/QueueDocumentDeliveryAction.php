<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use InvalidArgumentException;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Jobs\DispatchDocumentJob;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\RecipientOverride;

/**
 * Queues delivery when config e-billing.delivery.enabled is true.
 */
final class QueueDocumentDeliveryAction
{
    public const ACTIVITY_EVENT_RECIPIENT_OVERRIDE = 'delivery_recipient_overridden';

    /**
     * @param  array<int, mixed>|null  $channelKeys  null = every configured channel
     * @param  array<int, mixed>|null  $recipientOverride  selective redispatch only (ADR 0016); blank entries
     *                                                     are dropped, nothing left = no override
     */
    public function execute(EbillingDocument $document, ?array $channelKeys = null, ?array $recipientOverride = null): void
    {
        if (! (bool) config('e-billing.delivery.enabled', false)) {
            return;
        }

        $normalized = $this->normalizeChannelKeys($channelKeys);
        $override = RecipientOverride::normalize($recipientOverride);

        if ($override !== null && $normalized === null) {
            throw new InvalidArgumentException('A recipient override requires an explicit channel selection.');
        }

        DispatchDocumentJob::dispatch((string) $document->getKey(), $normalized, $override);

        if ($override !== null) {
            $this->recordOverride($document, $normalized, $override);
        }
    }

    /**
     * @param  list<string>  $channelKeys
     * @param  list<string>  $recipients
     */
    private function recordOverride(EbillingDocument $document, array $channelKeys, array $recipients): void
    {
        if (! class_exists(MooxActivityLogger::class)) {
            return;
        }

        MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT_RECIPIENT_OVERRIDE, [
            'event' => self::ACTIVITY_EVENT_RECIPIENT_OVERRIDE,
            'entry_type' => 'log',
            'subject' => $document,
            'properties' => [
                'recipients' => $recipients,
                'channels' => $channelKeys,
            ],
        ]);
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
