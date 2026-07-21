<?php

declare(strict_types=1);

namespace Moox\Contact\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Contact\Models\Contact;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'status' => fake()->randomElement(config('contact.statuses', ['draft', 'active'])),
            'gender' => fake()->randomElement(config('contact.genders', ['unknown'])),
            'salutation_code' => fake()->optional()->randomElement(['mr', 'mrs', 'ms', 'mx', 'none']),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => trim($firstName.' '.$lastName),
            'academic_title' => fake()->optional(0.1)->randomElement(['Dr.', 'Prof.']),
            'job_title' => fake()->optional(0.6)->jobTitle(),
            'note' => fake()->optional(0.2)->sentence(),
            'external_reference' => fake()->optional(0.3)->bothify('EXT-####'),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'mobile' => fake()->optional(0.7)->phoneNumber(),
            'email' => fake()->optional(0.8)->safeEmail(),
            'contact_type' => fake()->randomElement(config('contact.contact_types', ['external'])),
            'language_id' => null,
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
