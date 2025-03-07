<?php

namespace Moox\Connect\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Connect\Models\ApiConnection;

class ApiConnectionFactory extends Factory
{
    protected $model = ApiConnection::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'base_url' => $this->faker->url(),
            'api_type' => $this->faker->randomElement(['REST', 'GraphQL']),
            'auth_type' => 'jwt',
            'auth_credentials' => [
                'secret_key' => $this->faker->sha256(),
                'algorithm' => 'HS256',
            ],
            'status' => 'new',
        ];
    }

    public function jwt(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'auth_type' => 'jwt',
                'auth_credentials' => [
                    'secret_key' => $this->faker->sha256(),
                    'algorithm' => 'HS256',
                    'access_token' => null,
                    'refresh_token' => null,
                ],
            ];
        });
    }
}
