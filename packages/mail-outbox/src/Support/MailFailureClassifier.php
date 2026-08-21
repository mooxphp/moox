<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Moox\MailOutbox\Exceptions\PermanentMailFailureException;
use Moox\MailOutbox\Exceptions\TransientMailFailureException;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

final class MailFailureClassifier
{
    public function classify(Throwable $exception): FailureClassification
    {
        if ($exception instanceof TransientMailFailureException) {
            return new FailureClassification(
                FailureKind::Transient,
                $exception->retryAfterSeconds,
            );
        }

        if ($exception instanceof PermanentMailFailureException) {
            return new FailureClassification(FailureKind::Permanent);
        }

        if ($exception instanceof UnexpectedResponseException || $exception instanceof HttpTransportException) {
            $status = $this->extractHttpStatus($exception);

            if ($status === 429 || ($status !== null && $status >= 500)) {
                return new FailureClassification(
                    FailureKind::Transient,
                    $this->extractRetryAfterSeconds($exception),
                );
            }

            if ($status !== null && $status >= 400 && $status < 500) {
                return new FailureClassification(FailureKind::Permanent);
            }
        }

        if ($exception instanceof TransportExceptionInterface) {
            if ($this->looksLikePermanentRecipientFailure($exception)) {
                return new FailureClassification(FailureKind::Permanent);
            }

            return new FailureClassification(
                FailureKind::Transient,
                $exception instanceof TransportException
                    ? $this->extractRetryAfterSeconds($exception)
                    : null,
            );
        }

        return new FailureClassification(FailureKind::Permanent);
    }

    private function extractHttpStatus(Throwable $exception): ?int
    {
        $code = (int) $exception->getCode();

        if ($code >= 100 && $code <= 599) {
            return $code;
        }

        if (preg_match('/\b([45]\d{2})\b/', $exception->getMessage(), $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractRetryAfterSeconds(Throwable $exception): ?int
    {
        $haystack = $exception->getMessage();

        if ($exception instanceof TransportException) {
            $haystack .= "\n".$exception->getDebug();
        }

        if (preg_match('/retry[_ -]?after["\s:=]+(\d+)/i', $haystack, $matches) === 1) {
            return max(0, (int) $matches[1]);
        }

        return null;
    }

    private function looksLikePermanentRecipientFailure(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'invalid recipient')
            || str_contains($message, 'recipient rejected')
            || str_contains($message, 'mailbox unavailable')
            || str_contains($message, 'user unknown')
            || str_contains($message, 'malformed')
            || str_contains($message, '550 ')
            || str_contains($message, '553 ');
    }
}
