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

    public string $unitCode { get; }

    public float $lineTotal { get; }

    public ?string $deliveryDate { get; }

    public ?string $shipToName { get; }

    public ?ZugferdAddress $shipToAddress { get; }

    /** Purchase order line id inside the buyer PO (BT-132), not a PO document number. */
    public ?string $purchaseOrderLineReference { get; }

    /** Different PO document number on the line → BT-127 only (no core BT). */
    public ?string $orderDocumentReference { get; }

    public ?string $deliveryNoteNumber { get; }

    public ?string $purchaseOrderDate { get; }

    /** @var list<ZugferdItemAttribute> */
    public array $itemAttributes { get; }

    /** @var list<ZugferdItemClassification> */
    public array $itemClassifications { get; }

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges { get; }
}
