<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Contracts\Mail\Mailable;

final class OutboundMessagePreparer
{
    public function prepare(Mailable $mailable, string $header, string $correlationId): void
    {
        $mailable->withSymfonyMessage(function ($message) use ($header, $correlationId): void {
            $headers = $message->getHeaders();

            if ($headers->has($header)) {
                $headers->remove($header);
            }

            $headers->addTextHeader($header, $correlationId);

            if (! $headers->has('Message-ID') && method_exists($message, 'generateMessageId')) {
                $headers->addIdHeader('Message-ID', $message->generateMessageId());
            }
        });
    }
}
