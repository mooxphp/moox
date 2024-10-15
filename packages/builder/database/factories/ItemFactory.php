<?php

namespace Moox\Builder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Builder\Models\Item;

class ItemFactory extends Factory
{
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
            'slug' => $this->faker->unique()->slug,
            'featured_image_url' => $this->faker->image(null, 30, 30),
            'content' => $this->faker->paragraph,
            'gallery_image_urls' => null,
            'status' => 'draft',
            'type' => 'post',
            'author_id' => null,
            'publish_at' => null,
            'deleted_at' => null,
        ];
    }
}
