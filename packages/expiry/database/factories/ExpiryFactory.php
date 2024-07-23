<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Expiry\Models\Expiry;

class ExpiryFactory extends Factory
{
    protected $model = Expiry::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            // Fill in other fields with appropriate fake data
            'item_id' => $this->faker->randomDigitNotNull,
            'meta_id' => $this->faker->randomDigitNotNull,
            'link' => $this->faker->url,
            'expiry_job' => $this->faker->word,
            'category' => $this->faker->word,
            'status' => $this->faker->word,
            'expired_at' => now()->addDays(10),
            'notified_at' => now(),
            'notified_to' => $this->faker->randomDigitNotNull,
            'escalated_at' => now()->addDays(5),
            'escalated_to' => $this->faker->randomDigitNotNull,
            'handled_by' => $this->faker->randomDigitNotNull,
            'done_at' => now()->addDays(20),
        ];
    }
}
