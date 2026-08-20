<?php

declare(strict_types=1);

namespace Moox\MailInbox;

use DateTimeImmutable;

/**
 * Provider-agnostic representation of a single inbox message returned by a driver.
 *
 * @phpstan-type AttachmentDto array{id: string|int, name: string, content_type: string, size: int}
 */
readonly class InboxMessageDto
{
    /**
     * @param  string  $externalId  Stable identifier the driver guarantees across fetches.
     * @param  array<int, AttachmentDto>  $attachments
     * @param  string|null  $messageId  RFC822 Message-ID when the provider exposes it (dual-key dedup).
     */
    public function __construct(
        public string $externalId,
        public string $subject,
        public string $from,
        public DateTimeImmutable $receivedAt,
        public ?string $bodyHtml,
        public ?string $bodyText,
        public array $attachments = [],
        public ?string $messageId = null,
        public ?string $fromName = null,
        public ?string $toEmail = null,
        public ?string $toName = null,
        public bool $hasAttachments = false,
    ) {
    }
}
