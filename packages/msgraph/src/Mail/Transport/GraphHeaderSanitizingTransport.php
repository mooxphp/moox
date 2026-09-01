<?php

declare(strict_types=1);

namespace Moox\MsGraph\Mail\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

/**
 * Microsoft Graph only accepts custom internet message headers whose names
 * start with "x-" or "X-". Strip disallowed headers before delegating.
 */
final class GraphHeaderSanitizingTransport implements TransportInterface
{
    /**
     * Header names Graph rejects in internetMessageHeaders (lowercase).
     *
     * @var list<string>
     */
    private const STRIP_HEADERS = [
        'message-id',
    ];

    public function __construct(
        private readonly TransportInterface $inner,
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if ($message instanceof Message) {
            $message = clone $message;

            foreach (self::STRIP_HEADERS as $name) {
                if ($message->getHeaders()->has($name)) {
                    $message->getHeaders()->remove($name);
                }
            }
        }

        return $this->inner->send($message, $envelope);
    }

    public function __toString(): string
    {
        return (string) $this->inner;
    }
}
