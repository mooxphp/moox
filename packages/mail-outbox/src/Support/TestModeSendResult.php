<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Moox\MailOutbox\Enums\MailSendStatus;

final readonly class TestModeSendResult
{
    /**
     * @param  list<string>  $actualRecipients
     */
    public function __construct(
        public mixed $sent,
        public array $actualRecipients,
        public MailSendStatus $status,
    ) {
    }
}
