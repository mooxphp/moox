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
            'search_terms' => null,
            'parent_id' => null,
            'external_reference' => fake()->optional(0.3)->bothify('EXT-####'),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'fax' => fake()->optional(0.1)->phoneNumber(),
            'url' => fake()->optional(0.5)->url(),
            'email' => fake()->optional(0.8)->companyEmail(),
            'tax_number' => fake()->optional(0.4)->numerify('########'),
            'vat_number' => fake()->optional(0.5)->bothify('DE#########'),
            'has_no_vat_number' => false,
            'partner_type' => null,
            'partner_id' => null,
            'company_type' => fake()->randomElement(config('company.company_types', ['customer'])),
            'default_currency_code' => config('company.default_currency_code', 'EUR'),
            'is_fully_owned_subsidiary' => false,
            'no_marketing_action' => fake()->boolean(10),
            'no_marketing_action_reason' => null,
            'language_id' => null,
            'localization_id' => null,
            'sort' => fake()->optional(0.3)->numberBetween(1, 999),
            'is_active' => true,
            'approved_at' => null,
            'data' => null,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (): array => [
            'company_type' => 'customer',
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (): array => [
            'company_type' => 'supplier',
        ]);
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
            'is_active' => false,
            'status' => 'inactive',
        ]);
    }

    public function withParent(Company $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->getKey(),
            'is_fully_owned_subsidiary' => fake()->boolean(70),
        ]);
    }
}
