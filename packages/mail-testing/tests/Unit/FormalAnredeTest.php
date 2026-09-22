<?php

declare(strict_types=1);

use Moox\Contact\Models\Contact;
use Moox\MailTesting\Support\FormalAnrede;
use Tests\TestCase;

uses(TestCase::class);

it('builds a formal male greeting with title', function (): void {
    $contact = new Contact([
        'salutation_code' => 'mr',
        'academic_title' => 'Dr.',
        'last_name' => 'Müller',
        'first_name' => 'Hans',
    ]);

    expect(FormalAnrede::fromContact($contact))->toBe('Sehr geehrter Herr Dr. Müller');
});

it('builds a formal female greeting', function (): void {
    $contact = new Contact([
        'salutation_code' => 'mrs',
        'academic_title' => null,
        'last_name' => 'Schmidt',
        'first_name' => 'Anna',
    ]);

    expect(FormalAnrede::fromContact($contact))->toBe('Sehr geehrte Frau Schmidt');
});

it('falls back without a last name or salutation', function (): void {
    $contact = new Contact([
        'salutation_code' => 'mx',
        'last_name' => 'Wolf',
        'first_name' => 'Alex',
    ]);

    expect(FormalAnrede::fromContact($contact))->toBe('Sehr geehrte Damen und Herren');

    $contact->salutation_code = 'mr';
    $contact->last_name = '';

    expect(FormalAnrede::fromContact($contact))->toBe('Sehr geehrte Damen und Herren');
});
