<?php

declare(strict_types=1);

use Moox\Address\Exceptions\DuplicateAddressException;
use Moox\Address\Models\Address;

/**
 * @return array<string, mixed>
 */
function sampleAddressAttributes(): array
{
    return [
        'street' => 'Musterstraße',
        'street2' => null,
        'postal_code' => '10115',
        'city' => 'Berlin',
        'state' => null,
        'country_code' => 'DE',
        'is_primary' => false,
    ];
}

it('creates an address via factory', function (): void {
    $address = Address::factory()->create();

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->exists)->toBeTrue()
        ->and($address->street)->not->toBeEmpty();
});

it('builds a formatted line from postal fields', function (): void {
    $address = Address::factory()->create([
        'street' => 'Hauptstr. 1',
        'postal_code' => '10115',
        'city' => 'Berlin',
        'country_code' => 'DE',
    ]);

    expect($address->formattedLine())
        ->toContain('Hauptstr. 1')
        ->toContain('Berlin')
        ->toContain('DE');
});

it('treats addresses with the same postal fingerprint as duplicates', function (): void {
    $attributes = sampleAddressAttributes();

    Address::query()->create($attributes);

    expect(fn () => Address::query()->create($attributes))
        ->toThrow(DuplicateAddressException::class);
});

it('allows addresses that differ in street postal code or country', function (): void {
    $attributes = sampleAddressAttributes();

    Address::query()->create($attributes);

    $second = Address::query()->create([
        ...$attributes,
        'street' => 'Andere Straße 9',
    ]);

    expect($second)->toBeInstanceOf(Address::class)
        ->and(Address::query()->count())->toBe(2);
});

it('ignores city state and is_primary when detecting duplicates', function (): void {
    $attributes = sampleAddressAttributes();

    Address::query()->create([
        ...$attributes,
        'is_primary' => true,
    ]);

    expect(fn () => Address::query()->create([
        ...$attributes,
        'city' => 'Hamburg',
        'state' => 'HH',
        'is_primary' => false,
    ]))->toThrow(DuplicateAddressException::class);
});

it('allows updating an address without triggering a duplicate of itself', function (): void {
    $address = Address::query()->create(sampleAddressAttributes());

    $address->update(['city' => 'Updated City']);

    expect($address->fresh()->city)->toBe('Updated City');
});
