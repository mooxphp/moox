<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

/**
 * Transport-level result. {@see $accepted} means the send path accepted the
 * message (e.g. provider accepted) — not that a mailbox received it.
 *
 * {@see $actualRecipient} is the address the transport used (e.g. after a
 * sandbox redirect). When null, the channel records the resolved business recipient.
 */
final readonly class InvoiceMailSendResult
{
    public function __construct(
        public bool $accepted,
        public ?string $correlationId = null,
        public ?string $failureReason = null,
        public ?string $actualRecipient = null,
    ) {
    }
}
