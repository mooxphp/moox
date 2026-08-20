<?php

declare(strict_types=1);

namespace Moox\MailInbox\Exceptions;

use RuntimeException;
use Throwable;

/**
 * The provider rejected a specific sync cursor (expired or malformed resume token).
 *
 * Drivers must raise this only when the cursor itself is unusable — not as a
 * general error mapping. If the driver cannot distinguish cursor rejection from
 * other failures, it should raise its own transport exception instead.
 *
 * `FetchMailsJob` clears the stored cursor and starts a fresh sync, bounded
 * by `mail-inbox.cursor_reset_max_per_run`.
 *
 * When `$rejectedHost` is set, the cursor pointed at an unexpected host (possible
 * tampering) and the job logs at error level before clearing.
 */
class InvalidSyncCursorException extends RuntimeException
{
    public function __construct(
        string $message = 'Inbox sync cursor is invalid or expired.',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?string $rejectedHost = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
