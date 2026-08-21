<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Tests\Support;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Throwable;

/**
 * Test double transport that always throws a configured exception.
 */
final class ThrowingTransport implements TransportInterface
{
    public int $sendAttempts = 0;

    public function __construct(
        private readonly Throwable $exception,
    ) {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $this->sendAttempts++;

        throw $this->exception;
    }

    public function __toString(): string
    {
        return 'throwing://';
    }
}
