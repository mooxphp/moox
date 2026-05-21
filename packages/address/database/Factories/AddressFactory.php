<?php

declare(strict_types=1);

namespace Moox\Address\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Address\Models\Address;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->optional(0.25)->randomElement([
                'Headquarter',
                'Warehouse',
                'Office',
                'Branch',
            ]),
            'name' => fake()->company(),
            'street' => fake()->streetName().' '.fake()->buildingNumber(),
            'street2' => fake()->optional(0.15)->secondaryAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'state' => fake()->optional(0.4)->state(),
            'country_code' => fake()->countryCode(),
            'is_primary' => false,
            'data' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => [
            'is_primary' => true,
        ]);
    }

    public function withoutLabel(): static
    {
        return $this->state(fn (): array => [
            'label' => null,
        ]);
    }
}
