<?php

declare(strict_types=1);

use Moox\Address\Exceptions\DuplicateAddressException;
use Moox\Address\Models\Address;

it('creates an address via factory', function (): void {
    $address = Address::factory()->create();

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->exists)->toBeTrue()
        ->and($address->name)->not->toBeEmpty();
});

it('builds a formatted line from postal fields', function (): void {
    $address = Address::factory()->create([
        'name' => 'Muster GmbH',
        'street' => 'Hauptstr. 1',
        'postal_code' => '10115',
        'city' => 'Berlin',
        'country_code' => 'DE',
    ]);

    expect($address->formattedLine())
        ->toContain('Muster GmbH')
        ->toContain('Berlin')
        ->toContain('DE');
});

it('treats addresses with the same postal fingerprint as duplicates', function (): void {
    $attributes = $this->sampleAddressAttributes();

    Address::query()->create($attributes);

    expect(fn () => Address::query()->create($attributes))
        ->toThrow(DuplicateAddressException::class);
});

it('allows addresses that differ in street postal code or country', function (): void {
    $attributes = $this->sampleAddressAttributes();

    Address::query()->create($attributes);

    $second = Address::query()->create([
        ...$attributes,
        'street' => 'Andere Straße 9',
    ]);

    expect($second)->toBeInstanceOf(Address::class)
        ->and(Address::query()->count())->toBe(2);
});

it('allows the same name when street postal code and country differ', function (): void {
    $attributes = $this->sampleAddressAttributes();

    Address::query()->create($attributes);

    $second = Address::query()->create([
        ...$attributes,
        'name' => 'Anderer Empfänger GmbH',
        'street' => 'Nebenstraße 2',
    ]);

    expect($second)->toBeInstanceOf(Address::class)
        ->and(Address::query()->count())->toBe(2);
});

it('treats the same name as duplicate when street postal code and country match', function (): void {
    $attributes = $this->sampleAddressAttributes();

    Address::query()->create($attributes);

    expect(fn () => Address::query()->create([
        ...$attributes,
        'name' => 'Anderer Empfänger GmbH',
    ]))->toThrow(DuplicateAddressException::class);
});

it('ignores label name city state and is_primary when detecting duplicates', function (): void {
    $attributes = $this->sampleAddressAttributes();

    Address::query()->create([
        ...$attributes,
        'label' => 'Hauptsitz',
        'is_primary' => true,
    ]);

    expect(fn () => Address::query()->create([
        ...$attributes,
        'label' => 'Lager',
        'name' => 'Anderer Name',
        'city' => 'Hamburg',
        'state' => 'HH',
        'is_primary' => false,
    ]))->toThrow(DuplicateAddressException::class);
});

it('allows updating an address without triggering a duplicate of itself', function (): void {
    $address = Address::query()->create($this->sampleAddressAttributes());

    $address->update(['label' => 'Updated label']);

    expect($address->fresh()->label)->toBe('Updated label');
});
