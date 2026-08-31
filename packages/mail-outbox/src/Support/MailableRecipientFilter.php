<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\Mailable as IlluminateMailable;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class MailableRecipientFilter
{
    /**
     * @param  list<string>  $allowedEmails
     */
    public function filterToOnly(Mailable $mailable, array $allowedEmails): Mailable
    {
        $allowed = array_map(strtolower(...), $allowedEmails);
        $clone = clone $mailable;

        if (! $clone instanceof IlluminateMailable) {
            return $clone;
        }

        foreach (['to', 'cc', 'bcc'] as $property) {
            if (! property_exists($clone, $property)) {
                continue;
            }

            $entries = $clone->{$property};

            if (! is_array($entries)) {
                continue;
            }

            $clone->{$property} = array_values(array_filter(
                $entries,
                fn (mixed $entry): bool => $this->entryMatchesAllowed($entry, $allowed),
            ));
        }

        $clone->withSymfonyMessage(function ($message) use ($allowed): void {
            if (! $message instanceof Email || $allowed === []) {
                return;
            }

            $addresses = array_map(
                static fn (string $email): Address => new Address($email),
                $allowed,
            );

            $message->to(...$addresses);
            $message->cc();
            $message->bcc();
        });

        return $clone;
    }

    public function withSubject(Mailable $mailable, string $subject): Mailable
    {
        $clone = clone $mailable;

        if ($clone instanceof IlluminateMailable) {
            $clone->subject = $subject;
        }

        if (property_exists($clone, 'mailSubject')) {
            $clone->mailSubject = $subject;
        }

        return $clone;
    }

    /**
     * @param  list<string>  $allowed
     */
    private function entryMatchesAllowed(mixed $entry, array $allowed): bool
    {
        $email = $this->normalizeEntry($entry);

        return $email !== null && in_array($email, $allowed, true);
    }

    private function normalizeEntry(mixed $entry): ?string
    {
        if (is_string($entry) && $entry !== '') {
            return strtolower($entry);
        }

        if (is_array($entry) && isset($entry['address']) && is_string($entry['address']) && $entry['address'] !== '') {
            return strtolower($entry['address']);
        }

        if (is_object($entry) && property_exists($entry, 'address') && is_string($entry->address) && $entry->address !== '') {
            return strtolower($entry->address);
        }

        if (is_object($entry) && method_exists($entry, 'getAddress')) {
            $address = $entry->getAddress();

            return is_string($address) && $address !== '' ? strtolower($address) : null;
        }

        return null;
    }
}
