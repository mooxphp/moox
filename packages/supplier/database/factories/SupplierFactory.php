<?php

declare(strict_types=1);

namespace Moox\Supplier\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Supplier\Models\Supplier;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(config('supplier.statuses', ['draft', 'active'])),
            'supplier_number' => strtoupper(fake()->unique()->bothify('S-#####')),
            'external_reference' => fake()->optional(0.3)->numerify('#####'),
            'search_terms' => fake()->optional(0.3)->words(3, true),
            'discount_percent' => fake()->optional(0.4)->randomFloat(2, 0, 15),
            'lead_time_days' => fake()->optional(0.7)->numberBetween(1, 60),
            'minimum_order_value' => fake()->optional(0.4)->randomFloat(2, 100, 5000),
            'language_id' => null,
            'is_preferred' => fake()->boolean(20),
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
