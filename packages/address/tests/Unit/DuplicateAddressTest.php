<?php

declare(strict_types=1);

use Moox\Address\Exceptions\DuplicateAddressException;
use Moox\Address\Models\Address;

it('prevents creating a duplicate address through the model', function (): void {
    $attributes = $this->sampleAddressAttributes();

    Address::factory()->create($attributes);

    expect(fn () => Address::factory()->create($attributes))
        ->toThrow(DuplicateAddressException::class);

    expect(Address::query()->count())->toBe(1);
});

it('normalizes country code case when comparing duplicates', function (): void {
    Address::factory()->create([
        ...$this->sampleAddressAttributes(),
        'country_code' => 'de',
    ]);

    expect(fn () => Address::factory()->create([
        ...$this->sampleAddressAttributes(),
        'country_code' => 'DE',
    ]))->toThrow(DuplicateAddressException::class);
});

it('does not match soft-deleted addresses as duplicates', function (): void {
    $attributes = $this->sampleAddressAttributes();

    $deleted = Address::factory()->create($attributes);
    $deleted->delete();

    $replacement = Address::factory()->create($attributes);

    expect($replacement->exists)->toBeTrue()
        ->and(Address::withTrashed()->count())->toBe(2)
        ->and(Address::query()->count())->toBe(1);
});
