<?php

declare(strict_types=1);

namespace Moox\Invoice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\InvoiceModels;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $net = fake()->randomFloat(2, 100, 10_000);
        $vatRate = 19.00;
        $vatAmount = round($net * ($vatRate / 100), 2);

        return [
            'invoice_number' => fake()->unique()->numerify('INV-#####'),
            'invoice_date' => fake()->date('Y-m-d'),
            'document_type' => '380',
            'due_date' => fake()->optional()->date('Y-m-d'),
            'currency' => 'EUR',
            'customer_reference' => fake()->optional()->bothify('REF-####'),
            'order_number' => fake()->optional()->bothify('PO-####'),
            'order_date' => fake()->optional()->date('Y-m-d'),
            'pricing_basis' => null,
            'seller' => $this->sampleParty('Seller'),
            'buyer' => $this->sampleParty('Buyer'),
            'delivery' => $this->sampleAddress(),
            'net_total' => $net,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'gross_total' => $net + $vatAmount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleParty(string $label): array
    {
        return [
            'name' => fake()->company().' '.$label,
            'vat_id' => fake()->optional()->bothify('DE#########'),
            'tax_number' => fake()->optional()->numerify('########'),
            'address' => $this->sampleAddress(),
            'contact' => [
                'name' => fake()->name(),
                'phone' => fake()->optional()->phoneNumber(),
                'email' => fake()->optional()->companyEmail(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleAddress(): array
    {
        return [
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'subdivision' => null,
            'country_code' => 'DE',
        ];
    }

    public function modelName(): string
    {
        return InvoiceModels::invoice();
    }
}
