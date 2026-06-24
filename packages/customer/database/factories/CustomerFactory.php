<?php

declare(strict_types=1);

namespace Moox\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Customer\Models\Customer;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(config('customer.statuses', ['draft', 'active'])),
            'customer_number' => strtoupper(fake()->unique()->bothify('C-#####')),
            'external_reference' => fake()->optional(0.3)->numerify('#####'),
            'search_terms' => fake()->optional(0.3)->words(3, true),
            'price_type' => fake()->optional(0.6)->randomElement(config('customer.price_types', ['standard'])),
            'customer_group' => fake()->optional(0.3)->randomElement(['retail', 'wholesale', 'key_account']),
            'discount_percent' => fake()->optional(0.3)->randomFloat(2, 0, 25),
            'credit_limit' => fake()->optional(0.5)->randomFloat(2, 1000, 100000),
            'language_id' => null,
            'note' => fake()->optional(0.2)->sentence(),
            'sort' => fake()->optional(0.2)->numberBetween(1, 100),
            'is_active' => true,
            'approved_at' => null,
            'data' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'status' => 'inactive',
        ]);
    }
}
