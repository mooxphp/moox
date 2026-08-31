<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Enums;

enum MailSendSource: string
{
    case Outbox = 'outbox';

    /** Send recorded from Laravel's MessageSent event (not via SendMailJob). */
    case Recorded = 'recorded';
}
