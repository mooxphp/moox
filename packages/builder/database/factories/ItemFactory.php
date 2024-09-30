<?php

namespace Moox\Builder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Builder\Models\Item;

class ItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            'content' => $this->faker->paragraph,
            'status' => 'published',
            'type' => 'post',
        ];
    }
}
