<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Moox\MailOutbox\Contracts\ProviderMessageIdReader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Message;
use Throwable;

/**
 * Default reader: extracts a provider-stamped id already present on the sent
 * Symfony message (transport response headers). Never uses RFC 5322 Message-ID —
 * that belongs on MailSendLog::$messageId only.
 *
 * Transports that assign ids only via a follow-up provider API should bind a
 * custom ProviderMessageIdReader (read-back is switchable per mailer).
 */
final class SymfonySentMessageProviderIdReader implements ProviderMessageIdReader
{
    /**
     * Provider-specific headers transports may stamp after accept.
     * Message-ID is intentionally absent.
     *
     * @var list<string>
     */
    private const HEADER_CANDIDATES = [
        'X-Message-ID',
        'X-SES-Message-ID',
        'X-Resend-Email-ID',
        'X-GM-Message-ID',
        'X-MS-Exchange-Message-ID',
    ];

    public function read(string $mailer, SentMessage $sentMessage): ?string
    {
        try {
            foreach ($this->messages($sentMessage) as $message) {
                $headers = $message->getHeaders();

                foreach (self::HEADER_CANDIDATES as $name) {
                    if (! $headers->has($name)) {
                        continue;
                    }

                    $value = $headers->get($name)?->getBodyAsString();

                    if (is_string($value) && $value !== '') {
                        return trim($value);
                    }
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return list<Message>
     */
    private function messages(SentMessage $sentMessage): array
    {
        $messages = [];

        $original = $sentMessage->getOriginalMessage();

        if ($original instanceof Message) {
            $messages[] = $original;
        }

        $sent = $sentMessage->getMessage();

        if ($sent instanceof Message) {
            $messages[] = $sent;
        }

        return $messages;
    }
}
