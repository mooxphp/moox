<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Contracts\Mail\Mailable;
use Moox\MailOutbox\Enums\MailSendSource;
use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Exceptions\CannotResendMailException;
use Moox\MailOutbox\Jobs\SendMailJob;
use Moox\MailOutbox\Models\MailSendLog;
use Throwable;

final class ResendMailService
{
    public function __construct(
        private MailOutboxConfig $config,
    ) {
    }

    public function canResend(MailSendLog $log): bool
    {
        if ($log->status === MailSendStatus::Suppressed) {
            return false;
        }

        if ($log->status === MailSendStatus::Queued) {
            return false;
        }

        if ($log->source !== MailSendSource::Outbox) {
            return false;
        }

        return filled($log->resend_payload);
    }

    public function resend(MailSendLog $log): void
    {
        if (! $this->canResend($log)) {
            throw new CannotResendMailException('This send log row cannot be resent.');
        }

        $mailable = $this->resolveMailable($log);

        SendMailJob::dispatch($mailable, $log->mailer, $log->related);
    }

    private function resolveMailable(MailSendLog $log): Mailable
    {
        $allowedMailables = $this->config->resendAllowedMailables();

        $unserializeOptions = $allowedMailables === []
            ? ['allowed_classes' => true]
            : ['allowed_classes' => $allowedMailables];

        try {
            $payload = decrypt((string) $log->resend_payload);
            $mailable = unserialize($payload, $unserializeOptions);
        } catch (Throwable $exception) {
            throw new CannotResendMailException('Stored resend payload could not be restored.', previous: $exception);
        }

        if ($mailable instanceof __PHP_Incomplete_Class) {
            throw new CannotResendMailException('Stored resend payload could not be restored.');
        }

        if ($allowedMailables !== []) {
            $permitted = false;

            foreach ($allowedMailables as $class) {
                if ($mailable instanceof $class) {
                    $permitted = true;

                    break;
                }
            }

            if (! $permitted) {
                throw new CannotResendMailException('Stored resend payload is not an allowed mailable class.');
            }
        }

        if (! $mailable instanceof Mailable) {
            throw new CannotResendMailException('Stored resend payload is not a mailable.');
        }

        return $mailable;
    }
}
