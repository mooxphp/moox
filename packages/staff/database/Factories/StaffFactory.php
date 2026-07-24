<?php

declare(strict_types=1);

namespace Moox\Staff\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Staff\Models\Staff;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'status' => fake()->randomElement(config('staff.statuses', ['draft', 'active'])),
            'legacy_id' => fake()->boolean(30) ? fake()->unique()->numberBetween(1, 99999) : null,
            'external_reference' => fake()->optional(0.3)->uuid(),
            'short_code' => strtoupper(fake()->unique()->bothify('??##')),
            'display_name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'job_title' => fake()->optional(0.7)->jobTitle(),
            'email' => fake()->optional(0.8)->safeEmail(),
            'phone' => fake()->optional(0.6)->phoneNumber(),
            'language_id' => null,
            'contact_id' => null,
            'is_internal' => true,
            'data' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => 'inactive',
        ]);
    }

    public function external(): static
    {
        return $this->state(fn (): array => [
            'is_internal' => false,
        ]);
    }
}
