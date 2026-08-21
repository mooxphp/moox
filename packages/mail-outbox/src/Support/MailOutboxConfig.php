<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

final class MailOutboxConfig
{
    public function maxMessageBytes(): int
    {
        return max(1, (int) config('mail-outbox.max_message_bytes', 10 * 1024 * 1024));
    }

    public function maxTries(): int
    {
        return max(1, (int) config('mail-outbox.retry.max_tries', 5));
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        /** @var mixed $backoff */
        $backoff = config('mail-outbox.retry.backoff', [60, 300, 900]);

        if (! is_array($backoff) || $backoff === []) {
            return [60, 300, 900];
        }

        return array_values(array_map(static fn (mixed $v): int => max(0, (int) $v), $backoff));
    }

    public function correlationHeader(): string
    {
        $header = (string) config('mail-outbox.correlation_header', 'X-Moox-Mail-Correlation-Id');

        return $header !== '' ? $header : 'X-Moox-Mail-Correlation-Id';
    }

    public function shouldReadBackProviderId(string $mailer): bool
    {
        /** @var mixed $mailers */
        $mailers = config('mail-outbox.mailers', []);

        if (is_array($mailers) && isset($mailers[$mailer]) && is_array($mailers[$mailer]) && array_key_exists('read_back_provider_id', $mailers[$mailer])) {
            return (bool) $mailers[$mailer]['read_back_provider_id'];
        }

        return (bool) config('mail-outbox.read_back_provider_id', false);
    }
}
