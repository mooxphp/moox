<?php

declare(strict_types=1);

use Moox\Contact\Models\Contact;

it('creates a contact via factory', function (): void {
    $contact = Contact::factory()->create();

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->exists)->toBeTrue()
        ->and($contact->getKey())->toBeString()
        ->and($contact->display_name)->not->toBeEmpty();
});

it('builds a display label from display name', function (): void {
    $contact = Contact::factory()->create([
        'display_name' => 'Muster Display',
    ]);

    expect($contact->displayLabel())->toBe('Muster Display');
});

it('falls back to email or username and never uses the UUID', function (): void {
    $withEmail = Contact::factory()->make([
        'display_name' => null,
        'first_name' => null,
        'last_name' => null,
        'email' => 'kontakt@example.test',
        'username' => 'kontakt.user',
    ]);

    $withUsername = Contact::factory()->make([
        'display_name' => null,
        'first_name' => null,
        'last_name' => null,
        'email' => null,
        'username' => 'nur.username',
    ]);

    $empty = Contact::factory()->create([
        'display_name' => null,
        'first_name' => null,
        'last_name' => null,
        'email' => null,
        'username' => null,
    ]);

    expect($withEmail->displayLabel())->toBe('kontakt@example.test')
        ->and($withUsername->displayLabel())->toBe('nur.username')
        ->and($empty->displayLabel())->toBe('')
        ->and($empty->getKey())->not->toBeEmpty()
        ->and($empty->displayLabel())->not->toBe((string) $empty->getKey());
});

it('ignores a UUID display name and uses email instead', function (): void {
    $contact = Contact::factory()->make([
        'display_name' => '019fd24d-a433-70ec-afab-2abfbe8748cd',
        'first_name' => null,
        'last_name' => null,
        'email' => 'kontakt@example.test',
        'username' => '019fd24d-a433-70ec-afab-2abfbe8748cd',
    ]);

    expect($contact->displayLabel())->toBe('kontakt@example.test');
});
