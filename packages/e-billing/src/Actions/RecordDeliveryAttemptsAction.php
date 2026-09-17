<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use InvalidArgumentException;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Data\DeliveryOutcome;
use Moox\EBilling\Models\EbillingDeliveryAttempt;
use Moox\EBilling\Models\EbillingDocument;

final class RecordDeliveryAttemptsAction
{
    public const ACTIVITY_EVENT = 'delivery_attempted';

    /**
     * @param  list<DeliveryOutcome>  $outcomes
     */
    public function execute(EbillingDocument $document, string $channelKey, array $outcomes): void
    {
        foreach ($outcomes as $outcome) {
            if (! $outcome instanceof DeliveryOutcome) {
                throw new InvalidArgumentException('Delivery outcomes must be DeliveryOutcome instances.');
            }

            $attempt = EbillingDeliveryAttempt::query()->create([
                'ebilling_document_id' => $document->getKey(),
                'channel' => $channelKey,
                'recipient' => $outcome->recipient,
                'success' => $outcome->success,
                'failure_reason' => $outcome->failureReason,
                'correlation_id' => $outcome->correlationId,
            ]);

            $this->recordActivity($document, $attempt);
        }
    }

    private function recordActivity(EbillingDocument $document, EbillingDeliveryAttempt $attempt): void
    {
        if (! class_exists(MooxActivityLogger::class)) {
            return;
        }

        MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
            'event' => self::ACTIVITY_EVENT,
            'entry_type' => 'log',
            'subject' => $document,
            'properties' => [
                'attempt_id' => $attempt->getKey(),
                'channel' => $attempt->channel,
                'recipient' => $attempt->recipient,
                'success' => $attempt->success,
                'failure_reason' => $attempt->failure_reason,
                'correlation_id' => $attempt->correlation_id,
            ],
        ]);
    }
}
