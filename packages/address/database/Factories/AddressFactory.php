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
            'street' => fake()->streetName().' '.fake()->buildingNumber(),
            'street2' => fake()->optional(0.15)->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'state' => fake()->optional(0.4)->randomElement(['BE', 'BY', 'HH', 'NW', 'HE']),
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
}
