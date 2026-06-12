<?php

declare(strict_types=1);

namespace Moox\Zugferd\Contracts;

interface ZugferdInvoiceLine
{
    public int $position { get; }

    public string $description { get; }

    public ?string $descriptionDetail { get; }

    public ?string $articleNumber { get; }

    public float $unitPrice { get; }

    public float $quantity { get; }

    public string $unit { get; }

    public float $lineTotal { get; }

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges { get; }
}
