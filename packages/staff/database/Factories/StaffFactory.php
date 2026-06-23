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
            'department' => fake()->optional(0.5)->randomElement(['Sales', 'Support', 'IT', 'Finance']),
            'email' => fake()->optional(0.8)->safeEmail(),
            'email_account' => fake()->optional(0.3)->safeEmail(),
            'phone' => fake()->optional(0.6)->phoneNumber(),
            'fax' => fake()->optional(0.2)->phoneNumber(),
            'language_code' => fake()->optional(0.5)->randomElement(['de', 'en']),
            'user_id' => null,
            'contact_id' => null,
            'sales_unit_guid' => fake()->optional(0.2)->uuid(),
            'sales_unit_id' => fake()->optional(0.2)->numberBetween(1, 50),
            'can_change' => fake()->boolean(30),
            'is_system_user' => fake()->boolean(40),
            'is_internal' => true,
            'is_user_for_services' => fake()->boolean(20),
            'is_active' => true,
            'bcc_on_mail_send' => fake()->boolean(10),
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

    public function external(): static
    {
        return $this->state(fn (): array => [
            'is_internal' => false,
        ]);
    }
}
