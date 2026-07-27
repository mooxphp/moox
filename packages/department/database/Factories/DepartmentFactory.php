<?php

declare(strict_types=1);

namespace Moox\Department\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Department\Models\Department;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Sales', 'Support', 'Finance', 'Management', 'Logistics', 'IT']);

        return [
            'status' => fake()->randomElement(config('department.statuses', ['draft', 'active'])),
            'name' => $name,
            'code' => strtoupper(fake()->unique()->bothify('DEP-###')),
            'description' => fake()->optional(0.4)->sentence(),
            'external_reference' => fake()->optional(0.3)->uuid(),
            'data' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => 'inactive',
        ]);
    }
}
