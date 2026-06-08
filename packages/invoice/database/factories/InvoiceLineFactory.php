<?php

declare(strict_types=1);

namespace Moox\Invoice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Invoice\Support\InvoiceModels;

/**
 * @extends Factory<InvoiceLine>
 */
class InvoiceLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 10);
        $unitPrice = fake()->randomFloat(2, 10, 500);
        $lineTotal = round($quantity * $unitPrice, 2);

        return [
            'invoice_id' => InvoiceModels::invoice()::factory(),
            'position' => fake()->numberBetween(1, 20),
            'unit' => 'Stück',
            'quantity' => $quantity,
            'description' => fake()->sentence(3),
            'description_detail' => fake()->optional()->paragraph(),
            'article_number' => fake()->optional()->bothify('ART-####'),
            'customs_tariff_number' => fake()->optional()->numerify('########'),
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'delivery' => null,
            'delivery_date' => fake()->optional()->date('Y-m-d'),
            'delivery_note_number' => fake()->optional()->bothify('DN-####'),
            'order_number' => fake()->optional()->bothify('PO-####'),
            'order_date' => fake()->optional()->date('Y-m-d'),
        ];
    }

    public function modelName(): string
    {
        return InvoiceModels::invoiceLine();
    }
}
