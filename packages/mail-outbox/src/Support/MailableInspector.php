<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\Mailable as IlluminateMailable;
use Illuminate\Mail\SentMessage as LaravelSentMessage;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;

final class MailableInspector
{
    /**
     * @return list<string>
     */
    public function recipients(Mailable $mailable): array
    {
        $addresses = [];

        foreach (['to', 'cc', 'bcc'] as $property) {
            $value = $this->addressList($mailable, $property);

            foreach ($value as $entry) {
                $email = $this->normalizeAddress($entry);

                if ($email !== null) {
                    $addresses[] = $email;
                }
            }
        }

        return array_values(array_unique($addresses));
    }

    public function subject(Mailable $mailable): ?string
    {
        if ($mailable instanceof IlluminateMailable && is_string($mailable->subject) && $mailable->subject !== '') {
            return $mailable->subject;
        }

        if (method_exists($mailable, 'envelope')) {
            $envelope = $mailable->envelope();

            if (is_object($envelope) && property_exists($envelope, 'subject') && is_string($envelope->subject) && $envelope->subject !== '') {
                return $envelope->subject;
            }
        }

        return null;
    }

    public function templateKey(Mailable $mailable): string
    {
        return $mailable::class;
    }

    /**
     * @param  array{mailer?: string|null}  $data
     */
    public function recordedSnapshotFromSent(
        LaravelSentMessage $sent,
        array $data,
        MailOutboxConfig $config,
    ): ?RecordedSentMailSnapshot {
        $symfonySent = $sent->getSymfonySentMessage();

        if ($symfonySent === null) {
            return null;
        }

        $recipients = $this->recipientsFromSent($symfonySent);

        return new RecordedSentMailSnapshot(
            mailer: $this->resolveMailer($data, $config),
            recipients: $recipients !== [] ? $recipients : null,
            subject: $this->subjectFromSent($symfonySent),
            messageId: $this->messageIdFromSent($symfonySent),
            correlationId: $this->correlationIdFromSent($symfonySent, $config->correlationHeader()),
        );
    }

    public function messageIdFromSent(SentMessage $sentMessage): ?string
    {
        $value = $this->headerFromSent($sentMessage, 'Message-ID');

        return $value !== null ? trim($value, '<>') : null;
    }

    public function correlationIdFromSent(SentMessage $sentMessage, string $header): ?string
    {
        if ($header === '') {
            return null;
        }

        return $this->headerFromSent($sentMessage, $header);
    }

    public function subjectFromSent(SentMessage $sentMessage): ?string
    {
        $original = $sentMessage->getOriginalMessage();

        if ($original instanceof Email) {
            $subject = $original->getSubject();

            if (is_string($subject) && $subject !== '') {
                return $subject;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function recipientsFromSent(SentMessage $sentMessage): array
    {
        $original = $sentMessage->getOriginalMessage();

        if (! $original instanceof Email) {
            return $this->recipientsFromEnvelope($sentMessage);
        }

        $addresses = [];

        foreach ([$original->getTo(), $original->getCc(), $original->getBcc()] as $group) {
            foreach ($group as $address) {
                $addresses[] = strtolower($address->getAddress());
            }
        }

        if ($addresses !== []) {
            return array_values(array_unique($addresses));
        }

        return $this->recipientsFromEnvelope($sentMessage);
    }

    /**
     * @return list<mixed>
     */
    private function addressList(Mailable $mailable, string $property): array
    {
        if ($mailable instanceof IlluminateMailable && property_exists($mailable, $property)) {
            $value = $mailable->{$property};

            return is_array($value) ? $value : [];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function recipientsFromEnvelope(SentMessage $sentMessage): array
    {
        $addresses = [];

        foreach ($sentMessage->getEnvelope()->getRecipients() as $address) {
            $addresses[] = strtolower($address->getAddress());
        }

        return array_values(array_unique($addresses));
    }

    private function normalizeAddress(mixed $entry): ?string
    {
        if (is_string($entry) && $entry !== '') {
            return strtolower($entry);
        }

        if (is_array($entry) && isset($entry['address']) && is_string($entry['address']) && $entry['address'] !== '') {
            return strtolower($entry['address']);
        }

        if ($entry instanceof Address) {
            return strtolower($entry->getAddress());
        }

        if (is_object($entry) && property_exists($entry, 'address') && is_string($entry->address) && $entry->address !== '') {
            return strtolower($entry->address);
        }

        return null;
    }

    private function headerFromSent(SentMessage $sentMessage, string $headerName): ?string
    {
        foreach ([$sentMessage->getOriginalMessage(), $sentMessage->getMessage()] as $candidate) {
            if (! $candidate instanceof Message) {
                continue;
            }

            $headers = $candidate->getHeaders();

            if (! $headers->has($headerName)) {
                continue;
            }

            $value = $headers->get($headerName)?->getBodyAsString();

            if (! is_string($value) || $value === '') {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * @param  array{mailer?: string|null}  $data
     */
    private function resolveMailer(array $data, MailOutboxConfig $config): string
    {
        /** @var mixed $mailer */
        $mailer = $data['mailer'] ?? $config->defaultMailer();

        return is_string($mailer) && $mailer !== '' ? $mailer : $config->defaultMailer();
    }
}
