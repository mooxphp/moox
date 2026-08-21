<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Contracts;

use Symfony\Component\Mailer\SentMessage;

/**
 * Read the provider-assigned message identifier after a successful send.
 *
 * Prefer ids already present on the SentMessage. Implementations that need a
 * follow-up provider API call (one call per message) may do so — enable that
 * only for mailers that need correlation (see config read_back_provider_id).
 *
 * Must never return the RFC 5322 Message-ID; that is stored separately as
 * MailSendLog::$messageId.
 */
interface ProviderMessageIdReader
{
    public function read(string $mailer, SentMessage $sentMessage): ?string;
}
