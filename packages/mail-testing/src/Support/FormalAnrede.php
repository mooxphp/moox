<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Moox\Contact\Models\Contact;

final class FormalAnrede
{
    public static function fromContact(Contact $contact): string
    {
        $lastName = trim((string) $contact->last_name);
        $academicTitle = trim((string) $contact->academic_title);
        $named = trim($academicTitle.' '.$lastName);

        return match ($contact->salutation_code) {
            'mr' => $lastName !== ''
                ? trim('Sehr geehrter Herr '.$named)
                : 'Sehr geehrte Damen und Herren',
            'mrs', 'ms' => $lastName !== ''
                ? trim('Sehr geehrte Frau '.$named)
                : 'Sehr geehrte Damen und Herren',
            default => 'Sehr geehrte Damen und Herren',
        };
    }
}
