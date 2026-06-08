<?php

declare(strict_types=1);

namespace Moox\MailInbox\Enums;

/**
 * Allowed `inbox_messages.processing_status` values (string column).
 *
 * String values for shared lifecycle states match {@see InboxAttachmentProcessingStatus} (`new`, `processing` if used,
 * `processed`, `failed`, `skipped`). Message-only values: `read`, `partially_failed`.
 *
 * - new: Message ingested; attachment and/or PDF pipeline not finished.
 * - read: Legacy / optional intermediate (e.g. manual); pipeline may still run.
 * - processed: All attachments terminal and successful path; Graph message moved to Processed when applicable.
 * - failed: Terminal error; Graph message may be in Failed folder.
 * - partially_failed: A job failed while other attachments were still new/processing; resolved when all attachments are terminal.
 * - skipped: Reserved for messages with no actionable content (rare); included for reporting symmetry.
 */
enum InboxMessageProcessingStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Processed = 'processed';
    case Failed = 'failed';
    case PartiallyFailed = 'partially_failed';
    case Skipped = 'skipped';
}
