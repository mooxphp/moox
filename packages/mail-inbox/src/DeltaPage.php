<?php

declare(strict_types=1);

namespace Moox\MailInbox;

use Microsoft\Graph\Generated\Models\Message;

/**
 * Single page result from Graph {@code GET .../messages/delta} or a follow URL.
 */
readonly class DeltaPage
{
    /**
     * @param  array<int, Message>  $messages  Delta `value` entries without @removed placeholders.
     */
    public function __construct(
        public array $messages,
        public ?string $nextLink,
        public ?string $deltaLink,
        public int $removedFiltered,
    ) {
    }
}
