<?php

declare(strict_types=1);

namespace Moox\Restore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RestoreDestinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id' => $this->faker->numberBetween(1, 100),
            'host' => $this->faker->domainName,
            'env_data' => [
                "APP_URL" => "\"https://" . $this->faker->domainName . "\"",
                "APP_NAME" => "\"" . $this->faker->company . "\"",
                "DB_DATABASE" => $this->faker->word,
                "DB_PASSWORD" => "\"" . $this->faker->password . "\"",
                "DB_USERNAME" => $this->faker->userName,
            ],
        ];
    }
}
