<?php

declare(strict_types=1);

namespace Moox\EBilling\Tests\Support;

use Moox\EBilling\Models\EbillingDocument;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;

final readonly class PipelineFixture
{
    public function __construct(
        public InboxMessage $message,
        public InboxAttachment $attachment,
        public EbillingDocument $document,
    ) {
    }

    /**
     * @param  array{message: InboxMessage, attachment: InboxAttachment, document: EbillingDocument}  $fixture
     */
    public static function fromArray(array $fixture): self
    {
        return new self(
            message: $fixture['message'],
            attachment: $fixture['attachment'],
            document: $fixture['document'],
        );
    }
}
