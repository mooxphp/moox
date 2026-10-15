<?php

declare(strict_types=1);

namespace Moox\Department\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
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
        $title = fake()->randomElement(['Sales', 'Support', 'Finance', 'Management', 'Logistics', 'IT']);

        return [
            'is_active' => true,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'note' => fake()->optional(0.4)->sentence(),
            'external_reference' => fake()->optional(0.3)->uuid(),
            'external_info' => fake()->optional()->sentence(),
            'external_status' => null,
            'contact_id' => 1,
            'data' => null,
            'created_by_id' => 1,
            'created_by_type' => 'App\\Models\\User',
            'updated_by_id' => 1,
            'updated_by_type' => 'App\\Models\\User',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
