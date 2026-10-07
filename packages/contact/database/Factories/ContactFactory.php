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
        return [
            'is_active' => true,
            'name_1' => fake()->company(),
            'name_2' => fake()->optional()->company(),
            'name_3' => fake()->optional()->lastName(),
            'note' => fake()->optional()->sentence(),
            'external_reference' => fake()->optional()->bothify('EXT-####'),
            'external_info' => fake()->optional()->sentence(),
            'external_status' => null,
            'language_id' => null,
            'country_id' => null,
            'organization_type_id' => null,
            'legal_form_id' => null,
            'data_json' => [],
            'created_by_id' => 1,
            'created_by_type' => 'factory',
            'updated_by_id' => 1,
            'updated_by_type' => 'factory',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
