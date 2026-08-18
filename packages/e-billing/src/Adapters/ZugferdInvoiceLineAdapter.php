<?php

declare(strict_types=1);

namespace Moox\EBilling\Adapters;

use Moox\EBilling\Support\DeliveryDateTransmission;
use Moox\EBilling\Support\UnitCodeResolver;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdInvoiceLine;
use Moox\Zugferd\Data\AllowanceCharge;

final class ZugferdInvoiceLineAdapter implements ZugferdInvoiceLine
{
    private readonly string $resolvedUnitCode;

    public readonly ?string $deliveryDate;

    public function __construct(
        private InvoiceLine $line,
        private ?UnitCodeResolver $unitCodeResolver = null,
        private bool $emitLineDeliveryDate = false,
    ) {
        $this->unitCodeResolver ??= app(UnitCodeResolver::class);

        $persistedCode = trim((string) ($this->line->unit_code ?? ''));
        if ($persistedCode !== '') {
            $this->resolvedUnitCode = $this->unitCodeResolver->normalizePieceCode($persistedCode);
        } else {
            $unit = trim((string) ($this->line->unit ?? ''));
            $this->resolvedUnitCode = $unit !== ''
                ? $this->unitCodeResolver->resolveLabel($unit)
                : '';
        }

        $this->deliveryDate = self::resolveDeliveryDate($this->line, $this->emitLineDeliveryDate);
    }

    public int $position {
        get => (int) $this->line->position;
    }

    public string $description {
        get => (string) ($this->line->description ?? '');
    }

    public ?string $descriptionDetail {
        get => $this->line->description_detail;
    }

    public ?string $articleNumber {
        get => $this->line->article_number;
    }

    public float $unitPrice {
        get => (float) $this->line->unit_price;
    }

    public float $quantity {
        get => (float) $this->line->quantity;
    }

    public string $unit {
        get => (string) ($this->line->unit ?? '');
    }

    public string $unitCode {
        get => $this->resolvedUnitCode;
    }

    public float $lineTotal {
        get => (float) $this->line->line_total;
    }

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges {
        get {
            $this->line->loadMissing('allowanceCharges');

            return $this->line->allowanceCharges
                ->map(fn (InvoiceAllowanceCharge $charge): AllowanceCharge => self::mapAllowanceCharge($charge))
                ->values()
                ->all();
        }
    }

    private static function resolveDeliveryDate(InvoiceLine $line, bool $emitLineDeliveryDate): ?string
    {
        if (! $emitLineDeliveryDate) {
            return null;
        }

        $rawDate = $line->delivery_date !== null ? (string) $line->delivery_date : null;

        return DeliveryDateTransmission::normalize($rawDate);
    }

    private static function mapAllowanceCharge(InvoiceAllowanceCharge $charge): AllowanceCharge
    {
        return new AllowanceCharge(
            isCharge: (bool) $charge->is_charge,
            amount: (float) $charge->amount,
            reasonCode: $charge->reason_code,
            reasonText: $charge->reason_text,
            basisAmount: $charge->base_amount !== null ? (float) $charge->base_amount : null,
            percentage: $charge->percentage !== null ? (float) $charge->percentage : null,
        );
    }
}
