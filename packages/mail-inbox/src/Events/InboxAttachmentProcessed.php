<?php

declare(strict_types=1);

namespace Moox\MailInbox\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;

class InboxAttachmentProcessed
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  ?array{bill: mixed, xml: string}  $result  Filled by the first listener that completes PDF processing
     *                                                    (e.g. `ProcessInboxAttachmentListener` in moox/e-billing).
     *                                                    Remains null until then; listeners should only assign when `$result === null`.
     */
    public function __construct(
        public InboxMessage $message,
        public InboxAttachment $attachment,
        public ?array $result = null,
    ) {}
}
