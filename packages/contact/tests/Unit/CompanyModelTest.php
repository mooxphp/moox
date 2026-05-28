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
