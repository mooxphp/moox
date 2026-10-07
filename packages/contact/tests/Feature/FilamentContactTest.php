<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Session;
use Moox\Contact\Models\Contact;
use Moox\Contact\Resources\Contact\Pages\CreateContact;
use Moox\Contact\Resources\Contact\Pages\EditContact;
use Moox\Contact\Resources\Contact\Pages\ListContacts;
use Moox\DevTools\Models\TestUser;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Session::start();
    $this->actingAs(TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'test-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]));
});

it('can render the contact list page', function (): void {
    livewire(ListContacts::class)->assertSuccessful();
});

it('can render table columns for contacts', function (): void {
    livewire(ListContacts::class)
        ->assertTableColumnExists('name_1')
        ->assertTableColumnExists('display_name')
        ->assertTableColumnExists('is_active');
});

it('create form contains expected contact fields', function (): void {
    livewire(CreateContact::class)
        ->assertFormExists('form')
        ->assertFormFieldExists('name_1', 'form')
        ->assertFormFieldExists('name_2', 'form')
        ->assertFormFieldExists('external_reference', 'form');
});

it('can create a contact via filament', function (): void {
    livewire(CreateContact::class)
        ->fillForm([
            'name_1' => 'Muster GmbH',
            'name_2' => 'Nord',
            'is_active' => true,
        ], 'form')
        ->call('create')
        ->assertHasNoFormErrors();

    $contact = Contact::query()->where('name_1', 'Muster GmbH')->first();

    expect($contact)->not->toBeNull()
        ->and($contact->display_name)->toBe('Muster GmbH Nord')
        ->and($contact->created_by_id)->toBeInt();
});

it('can edit an existing contact via filament', function (): void {
    $contact = Contact::factory()->create([
        'name_1' => 'Muster GmbH',
        'name_2' => null,
        'name_3' => null,
    ]);

    livewire(EditContact::class, ['record' => $contact->getKey()])
        ->fillForm([
            'name_1' => 'Neue GmbH',
        ], 'form')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($contact->fresh()?->name_1)->toBe('Neue GmbH')
        ->and($contact->fresh()?->display_name)->toBe('Neue GmbH');
});
