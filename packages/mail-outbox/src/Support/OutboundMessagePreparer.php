<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Contracts\Mail\Mailable;

final class OutboundMessagePreparer
{
    public function __construct(
        private MailOutboxConfig $config,
    ) {}

    public function prepare(Mailable $mailable, string $header, string $correlationId, string $mailer): void
    {
        $mailable->withSymfonyMessage(function ($message) use ($header, $correlationId, $mailer): void {
            $headers = $message->getHeaders();

            if ($headers->has($header)) {
                $headers->remove($header);
            }

            $headers->addTextHeader($header, $correlationId);

            if (
                $this->config->shouldEnsureMessageId($mailer)
                && ! $headers->has('Message-ID')
                && method_exists($message, 'generateMessageId')
            ) {
                $headers->addIdHeader('Message-ID', $message->generateMessageId());
            }
        });
    }
}

