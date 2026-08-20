<?php

declare(strict_types=1);

namespace Moox\MailInbox\Contracts;

use Moox\MailInbox\Enums\ClaimResult;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\Exceptions\InvalidSyncCursorException;
use Moox\MailInbox\MessagePage;

/**
 * Transport-neutral contract for fetching and settling inbox messages.
 *
 * Outcomes, not destinations: no method takes a folder name and no driver
 * is required to have a concept of folders.
 *
 * Cursors returned by {@see fetch()} are opaque to this package. Because the
 * domain never inspects them, **validating a cursor is the driver's
 * responsibility** — including rejecting tokens that would send credentials
 * to an unexpected host.
 */
interface InboxDriver
{
    /**
     * Fetch a resumable page of messages.
     *
     * @param  string|null  $cursor  Opaque continuation or resume token from a previous page, or null for the first page.
     *
     * @throws InvalidSyncCursorException when the provider rejected this specific cursor (expired or malformed resume token). Do not use for general transport or API failures.
     */
    public function fetch(?string $cursor = null): MessagePage;

    /**
     * Claim a message for exclusive processing.
     */
    public function claim(string $externalId): ClaimResult;

    /**
     * Settle a previously claimed message with an outcome.
     *
     * What "settling" means is the driver's business — the Graph driver moves
     * messages between folders, an IMAP driver might flag or delete, a
     * webhook-based driver might do nothing.
     */
    public function settle(string $externalId, SettlementOutcome $outcome): void;

    /**
     * List file-attachment metadata for a message (no content bytes).
     *
     * @return list<array{id: string|int, name: string, content_type: string, size: int}>
     */
    public function listAttachments(string $externalId): array;

    /**
     * Read an attachment's raw content by external message id and attachment index or id.
     */
    public function readAttachment(string $externalId, string|int $attachmentId): string;
}
