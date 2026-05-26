<?php

declare(strict_types=1);

use Moox\Address\Models\Address;
use Moox\Address\Resources\Address\Pages\CreateAddress;
use Moox\Address\Resources\Address\Pages\EditAddress;
use Moox\Address\Resources\Address\Pages\ListAddresses;
use Moox\Address\Resources\AddressResource;
use Moox\DevTools\Models\TestUser;

    use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'test-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
    ]));
});

it('can render the address list page', function (): void {
    livewire(ListAddresses::class)->assertSuccessful();
});

it('can render table columns for addresses', function (): void {
    livewire(ListAddresses::class)
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('city')
        ->assertTableColumnExists('postal_code')
        ->assertTableColumnExists('country_code')
        ->assertTableColumnExists('is_primary');
});

it('create form contains expected address fields', function (): void {
    livewire(CreateAddress::class)
        ->assertFormExists('form')
        ->assertFormFieldExists('name', 'form')
        ->assertFormFieldExists('street', 'form')
        ->assertFormFieldExists('postal_code', 'form')
        ->assertFormFieldExists('city', 'form')
        ->assertFormFieldExists('country_code', 'form');
});

it('can create an address via filament', function (): void {
    livewire(CreateAddress::class)
        ->fillForm([
            'name' => 'Filament GmbH',
            'street' => 'Testweg 9',
            'postal_code' => '80331',
            'city' => 'München',
            'country_code' => 'DE',
            'is_primary' => false,
        ], 'form')
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Address::query()->where('name', 'Filament GmbH')->exists())->toBeTrue();
});

it('requires postal fields in the create form', function (): void {
    livewire(CreateAddress::class)
        ->fillForm([
            'name' => 'Incomplete GmbH',
            'street' => null,
            'postal_code' => null,
            'city' => null,
            'country_code' => null,
        ], 'form')
        ->call('create')
        ->assertHasFormErrors([
            'street',
            'postal_code',
            'city',
            'country_code',
        ]);
});

it('cannot create a duplicate address via filament', function (): void {
    $attributes = [
        'name' => 'Duplicate Co',
        'street' => 'Ring 1',
        'street2' => null,
        'state' => null,
        'postal_code' => '50667',
        'city' => 'Köln',
        'country_code' => 'DE',
        'is_primary' => false,
    ];
    $differentStreet = [
        ...$attributes,
        'street' => 'Ring 13',
    ];
    $differentPostalCode = [
        ...$attributes,
        'postal_code' => '50666',
    ];
    $differentCitySameLocation = [
        ...$attributes,
        'city' => 'Kölne',
    ];
    $sameLocationDifferentName = [
        ...$attributes,
        'name' => 'Another Recipient',
    ];

    Address::factory()->create($attributes);

    livewire(CreateAddress::class)
        ->fillForm($attributes, 'form')
        ->call('create')
        ->assertHasFormErrors(['street']);

    livewire(CreateAddress::class)
        ->fillForm($differentStreet, 'form')
        ->call('create')
        ->assertHasNoFormErrors();

    livewire(CreateAddress::class)
        ->fillForm($differentPostalCode, 'form')
        ->call('create')
        ->assertHasNoFormErrors();

    livewire(CreateAddress::class)
        ->fillForm($differentCitySameLocation, 'form')
        ->call('create')
        ->assertHasFormErrors(['street']);

    livewire(CreateAddress::class)
        ->fillForm($sameLocationDifferentName, 'form')
        ->call('create')
        ->assertHasFormErrors(['street']);

    expect(Address::query()->count())->toBe(3);
});

it('cannot save a duplicate address when editing via filament', function (): void {
    $existing = Address::factory()->create([
        'name' => 'Original GmbH',
        'street' => 'Hauptstraße 1',
        'street2' => null,
        'state' => null,
        'postal_code' => '10115',
        'city' => 'Berlin',
        'country_code' => 'DE',
    ]);

    $address = Address::factory()->create([
        'name' => 'Other GmbH',
        'street' => 'Nebenstraße 2',
        'street2' => null,
        'state' => null,
        'postal_code' => '20095',
        'city' => 'Hamburg',
        'country_code' => 'DE',
    ]);

    livewire(EditAddress::class, ['record' => $address->getKey()])
        ->fillForm([
            'name' => $existing->name,
            'street' => $existing->street,
            'street2' => null,
            'state' => null,
            'postal_code' => $existing->postal_code,
            'city' => $existing->city,
            'country_code' => $existing->country_code,
        ], 'form')
        ->call('save')
        ->assertHasFormErrors(['street']);

    expect($address->fresh()->name)->toBe('Other GmbH');
});

it('can edit an existing address via filament', function (): void {
    $address = Address::factory()->create([
        'name' => 'Old Name',
        'city' => 'Berlin',
        'country_code' => 'DE',
    ]);

    livewire(EditAddress::class, ['record' => $address->getKey()])
        ->fillForm([
            'name' => 'New Name',
        ], 'form')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($address->fresh()->name)->toBe('New Name');
});

it('can open address resource index via http', function (): void {
    $this->get(AddressResource::getUrl('index'))
        ->assertSuccessful();
});
