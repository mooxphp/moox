<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

final class MailOutboxConfig
{
    public const DEFAULT_MAILER = 'smtp';

    public const DEFAULT_CORRELATION_HEADER = 'X-Moox-Mail-Correlation-Id';

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
        $header = (string) config('mail-outbox.correlation_header', self::DEFAULT_CORRELATION_HEADER);

        return $header !== '' ? $header : self::DEFAULT_CORRELATION_HEADER;
    }

    public function shouldRecordForeignMail(): bool
    {
        return (bool) config('mail-outbox.record_foreign_mail', true);
    }

    public function defaultMailer(): string
    {
        $mailer = (string) config('mail.default', self::DEFAULT_MAILER);

        return $mailer !== '' ? $mailer : self::DEFAULT_MAILER;
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

    public function isTestModeEnabled(): bool
    {
        return (bool) config('mail-outbox.test_mode.enabled', false);
    }

    public function testModeRedirectTo(): string
    {
        return (string) config('mail-outbox.test_mode.redirect_to', '');
    }

    public function testModeRedirectName(): ?string
    {
        /** @var mixed $name */
        $name = config('mail-outbox.test_mode.redirect_name');

        if (! is_string($name) || $name === '') {
            return null;
        }

        return $name;
    }

    /**
     * @return list<string>
     */
    public function testModeAllowlistPatterns(): array
    {
        /** @var mixed $patterns */
        $patterns = config('mail-outbox.test_mode.allowlist', []);

        if (! is_array($patterns)) {
            return [];
        }

        return array_values(array_filter(
            $patterns,
            static fn (mixed $pattern): bool => is_string($pattern) && $pattern !== '',
        ));
    }

    public function testModeSubjectPrefix(): string
    {
        $prefix = (string) config('mail-outbox.test_mode.subject_prefix', '[TEST to %s] ');

        return $prefix !== '' ? $prefix : '[TEST to %s] ';
    }

    public function shouldWarnTestModeInProduction(): bool
    {
        return (bool) config('mail-outbox.test_mode.warn_in_production', true);
    }

    /**
     * @return list<class-string>
     */
    public function resendAllowedMailables(): array
    {
        /** @var mixed $allowed */
        $allowed = config('mail-outbox.resend.allowed_mailables', []);

        if (! is_array($allowed)) {
            return [];
        }

        return array_values(array_filter(
            $allowed,
            static fn (mixed $class): bool => is_string($class) && $class !== '' && class_exists($class),
        ));
    }
}
