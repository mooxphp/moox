<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Moox\Contact\Models\Contact;

final class MailTestingPayload
{
    /**
     * @return array{anrede: string, displayName: string, firstName: string, lastName: string}
     */
    public static function fromContact(Contact $contact): array
    {
        $firstName = trim((string) $contact->first_name);
        $lastName = trim((string) $contact->last_name);
        $displayName = trim((string) $contact->display_name);

        if ($displayName === '') {
            $displayName = trim($firstName.' '.$lastName);
        }

        return [
            'anrede' => FormalAnrede::fromContact($contact),
            'displayName' => $displayName !== '' ? $displayName : $firstName,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ];
    }
}
