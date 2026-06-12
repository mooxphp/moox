<?php

declare(strict_types=1);

namespace Moox\Invoice\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Invoice\Support\InvoiceModels;

/**
 * @extends Factory<InvoiceAllowanceCharge>
 */
class InvoiceAllowanceChargeFactory extends Factory
{
    private const ALLOWANCE_REASON_CODES = ['95', '60', '100'];

    private const CHARGE_REASON_CODES = ['FC', 'PC', 'AA'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCharge = fake()->boolean();

        $invoiceClass = InvoiceModels::invoice();

        return [
            'chargeable_type' => $invoiceClass,
            'chargeable_id' => $invoiceClass::factory(),
            'is_charge' => $isCharge,
            'amount' => fake()->randomFloat(2, 1, 500),
            'reason_code' => $isCharge
                ? fake()->randomElement(self::CHARGE_REASON_CODES)
                : fake()->randomElement(self::ALLOWANCE_REASON_CODES),
            'reason_text' => fake()->optional()->sentence(),
            'base_amount' => fake()->optional()->randomFloat(2, 100, 5000),
            'percentage' => fake()->optional()->randomFloat(2, 1, 25),
        ];
    }

    public function allowance(): static
    {
        return $this->state(fn (): array => [
            'is_charge' => false,
            'reason_code' => '95', // UNCL 5189 Discount
        ]);
    }

    public function charge(): static
    {
        return $this->state(fn (): array => [
            'is_charge' => true,
            'reason_code' => 'FC', // UNCL 7161 Freight service
        ]);
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (): array => [
            'chargeable_type' => $invoice->getMorphClass(),
            'chargeable_id' => $invoice->getKey(),
        ]);
    }

    public function forLine(InvoiceLine $line): static
    {
        return $this->state(fn (): array => [
            'chargeable_type' => $line->getMorphClass(),
            'chargeable_id' => $line->getKey(),
        ]);
    }

    public function modelName(): string
    {
        return InvoiceModels::invoiceAllowanceCharge();
    }
}
