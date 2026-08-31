<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Enums;

enum MailSendStatus: string
{
    case Queued = 'queued';

    /** Provider accepted the message and the send was logged — not mailbox delivery. */
    case Sent = 'sent';

    case Failed = 'failed';

    /** Redirected by safe test mode — provider may accept, but not delivered to intended recipients. */
    case Suppressed = 'suppressed';

    public function deliveredToIntendedRecipients(): bool
    {
        return $this === self::Sent;
    }
}
