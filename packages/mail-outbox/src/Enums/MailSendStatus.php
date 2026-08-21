<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Enums;

enum MailSendStatus: string
{
    case Queued = 'queued';

    /** Provider accepted the message and the send was logged — not mailbox delivery. */
    case Sent = 'sent';

    case Failed = 'failed';

    /** Reserved for safe test mode (later ticket); unused by SendMailJob today. */
    case Suppressed = 'suppressed';
}
