<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

final readonly class DeliveryOutcome
{
    public function __construct(
        public string $recipient,
        public bool $success,
        public ?string $failureReason = null,
        public ?string $correlationId = null,
    ) {
    }
}
