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

it('can render the company list page', function (): void {
    livewire(ListContacts::class)->assertSuccessful();
});

it('can render table columns for contacts', function (): void {
    livewire(ListContacts::class)
        ->assertTableColumnExists('display_name')
        ->assertTableColumnExists('contact_type')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('is_active');
});

it('create form contains expected contact fields', function (): void {
    livewire(CreateContact::class)
        ->assertFormExists('form')
        ->assertFormFieldExists('first_name', 'form')
        ->assertFormFieldExists('last_name', 'form')
        ->assertFormFieldExists('contact_type', 'form');
});

it('can create a contact via filament', function (): void {
    livewire(CreateContact::class)
        ->fillForm([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'display_name' => 'Max Mustermann',
            'status' => 'draft',
            'contact_type' => 'external',
            'gender' => 'unknown',
            'is_active' => true,
        ], 'form')
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Contact::query()->where('display_name', 'Max Mustermann')->exists())->toBeTrue();
});

it('can edit an existing contact via filament', function (): void {
    $contact = Contact::factory()->create([
        'display_name' => 'Max Mustermann',
        'status' => 'draft',
        'contact_type' => 'external',
        'is_active' => true,
    ]);

    livewire(EditContact::class, ['record' => $contact->getKey()])
        ->fillForm([
            'display_name' => 'New Name',
        ], 'form')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($contact->fresh()->display_name)->toBe('New Name');
});
