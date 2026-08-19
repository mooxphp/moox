<?php

declare(strict_types=1);

namespace Moox\MailInbox\Enums;

/**
 * Outcome reported back to the driver after a message has been through the pipeline.
 *
 * Drivers translate these into provider-specific actions (folder moves, flags, no-ops).
 */
enum SettlementOutcome: string
{
    case Processed = 'processed';
    case Failed = 'failed';

    /** Recognised and deliberately not processed. */
    case Ignored = 'ignored';
}
