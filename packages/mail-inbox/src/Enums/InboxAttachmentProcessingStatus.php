<?php

declare(strict_types=1);

namespace Moox\MailInbox\Enums;

/**
 * Allowed `inbox_attachments.processing_status` values (string column).
 *
 * String values for shared lifecycle states match {@see InboxMessageProcessingStatus} (`new`, `processing`, `processed`,
 * `failed`, `skipped`). Attachment rows use `processing` while work is in flight; messages may omit that value.
 *
 * - new: Stored on disk, waiting for PDF parse job / listener.
 * - processing: ParsePdfJob claimed the row; e-billing listener runs.
 * - processed: PDF pipeline completed successfully.
 * - failed: Parse or gateway error; see error_message.
 * - skipped: Non-PDF or intentionally ignored attachment.
 */
enum InboxAttachmentProcessingStatus: string
{
    case New = 'new';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
