<?php

namespace Moox\Item\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Item\Models\Item;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'custom_properties' => [],
        ];
    }
}
