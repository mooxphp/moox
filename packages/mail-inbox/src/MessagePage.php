<?php

declare(strict_types=1);

namespace Moox\MailInbox;

/**
 * A single page of messages returned by {@see InboxDriver::fetch()}.
 *
 * The cursor is opaque — the package never inspects or parses it.
 */
readonly class MessagePage
{
    /**
     * @param  array<int, InboxMessageDto>  $messages
     * @param  string|null  $nextCursor  Opaque token for the next page, null when this is the final page.
     */
    public function __construct(
        public array $messages,
        public ?string $nextCursor,
    ) {}
}
