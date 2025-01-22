<?php

namespace Moox\Press\Database\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Press\Models\WpUser;
use Moox\Security\Helper\PasswordHash;

class WpUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = WpUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasher = new PasswordHash(8, true);

        return [
            'ID' => $this->faker->unique()->numberBetween(1, 10000),
            'user_login' => fake()->userName(),
            'user_pass' => $hasher->HashPassword($this->generatePassword()),
            'user_nicename' => fake()->userName(),
            'user_email' => fake()->email(),
            'user_url' => fake()->url(),
            'user_registered' => fake()->time(),
            'user_activation_key' => '',
            'user_status' => 0,
            'display_name' => fake()->name(),
        ];
    }

    public function generatePassword($length = 12): string
    {
        // Define the character set for the password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_';

        // Get the length of the character set
        $charLength = strlen($chars);

        // Initialize the password variable
        $password = '';

        // Generate random bytes for password securely
        $randomBytes = random_bytes($length);

        // Convert the bytes to a string
        $randomString = base64_encode($randomBytes);

        // Loop through each character of the random string and choose characters from the character set
        for ($i = 0; $i < $length; $i++) {
            $index = ord($randomString[$i]) % $charLength;
            $password .= $chars[$index];
        }

        return $password;
    }
}
