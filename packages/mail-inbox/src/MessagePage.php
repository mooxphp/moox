<?php

declare(strict_types=1);

namespace Moox\MailInbox;

/**
 * A page of messages returned by {@see InboxDriver::fetch()}.
 *
 * Two opaque tokens — this package never inspects or parses them:
 *
 * - {@see $continuationCursor} — more pages remain in the current catch-up
 *   (Graph `@odata.nextLink`). Null when this delta round is finished.
 *   Still set when a per-run page limit stops mid-catch-up so the next run
 *   can resume there.
 * - {@see $resumeCursor} — where the next scheduled poll should start
 *   (Graph `@odata.deltaLink`). Set only on the last page of a round, when
 *   {@see $continuationCursor} is null.
 *
 * Pass whichever non-null token exists back into {@see InboxDriver::fetch()}.
 * Validating the token is the driver's responsibility.
 */
readonly class MessagePage
{
    /**
     * @param  array<int, InboxMessageDto>  $messages
     * @param  string|null  $continuationCursor  Next page of this run; null when the round is complete.
     * @param  string|null  $resumeCursor  Persist for the next run; only set when {@see $continuationCursor} is null.
     */
    public function __construct(
        public array $messages,
        public ?string $continuationCursor,
        public ?string $resumeCursor = null,
    ) {}
}
