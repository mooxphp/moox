<?php

declare(strict_types=1);

namespace Moox\Company\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Company\Models\Company;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'status' => fake()->randomElement(config('company.statuses', ['draft', 'active'])),
            'name' => $name,
            'display_name' => $name,
            'legal_name' => fake()->optional(0.6)->company().' '.fake()->randomElement(['GmbH', 'AG', 'KG', 'OHG', 'Ltd.']),
            'note' => fake()->optional(0.2)->sentence(),
            'parent_id' => null,
            'external_reference' => fake()->optional(0.3)->bothify('EXT-####'),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'fax' => fake()->optional(0.1)->phoneNumber(),
            'url' => fake()->optional(0.5)->url(),
            'email' => fake()->optional(0.8)->companyEmail(),
            'tax_number' => fake()->optional(0.4)->numerify('########'),
            'vat_number' => fake()->optional(0.5)->bothify('DE#########'),
            'default_currency_code' => config('company.default_currency_code', 'EUR'),
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

    public function withParent(Company $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->getKey(),
        ]);
    }
}
