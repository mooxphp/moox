<?php

declare(strict_types=1);

namespace Moox\Invoice\Support;

use Moox\Invoice\Support\En16931\Address;

readonly class InvoiceLineDraft
{
    /**
     * @param  list<ChargeDraft>  $charges
     * @param  array<string, mixed>  $extra  Additional attributes applied when present on the line model fillable (e.g. supplier-specific extension columns).
     */
    public function __construct(
        public int $position,
        public string $unit,
        public float $quantity,
        public ?string $description,
        public ?string $description_detail,
        public ?string $article_number,
        public ?string $customs_tariff_number,
        public float $unit_price,
        public float $line_total,
        public ?string $delivery_date,
        public ?string $delivery_note_number,
        public ?string $order_number,
        public ?string $order_date,
        public ?Address $delivery,
        public array $charges = [],
        public array $extra = [],
    ) {
    }
}
