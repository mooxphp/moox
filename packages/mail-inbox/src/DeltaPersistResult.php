<?php

declare(strict_types=1);

namespace Moox\MailInbox;

readonly class DeltaPersistResult
{
    public function __construct(
        public int $persisted,
        public int $skippedKnown,
        public int $skippedNoAttachments,
    ) {
    }
}
