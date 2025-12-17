<?php

namespace Moox\Bpmn\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BpmnFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Conference::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'title' => fake()->title(),
            'description' => fake()->text(),

            'status' => fake()->word(),

            'bpmn_id' => null,
        ];
    }
}
