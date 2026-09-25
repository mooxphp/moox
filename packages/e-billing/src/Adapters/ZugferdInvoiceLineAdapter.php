<?php

declare(strict_types=1);

namespace Moox\EBilling\Adapters;

use Moox\EBilling\Support\DeliveryDateTransmission;
use Moox\EBilling\Support\LineAllowanceChargeResolver;
use Moox\EBilling\Support\LineItemAttributeMapper;
use Moox\EBilling\Support\UnitCodeResolver;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Invoice\Support\En16931\Party;
use Moox\Zugferd\Contracts\ZugferdAddress;
use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdInvoiceLine;
use Moox\Zugferd\Contracts\ZugferdItemAttribute;
use Moox\Zugferd\Contracts\ZugferdItemClassification;
use Moox\Zugferd\Data\AllowanceCharge;

final class ZugferdInvoiceLineAdapter implements ZugferdInvoiceLine
{
    private readonly string $resolvedUnitCode;

    public readonly ?string $deliveryDate;

    public readonly ?string $shipToName;

    public readonly ?ZugferdAddress $shipToAddress;

    public readonly ?string $orderDocumentReference;

    public readonly ?string $deliveryNoteNumber;

    public readonly ?string $purchaseOrderDate;

    public readonly ?string $purchaseOrderLineReference;

    /** @var list<ZugferdItemAttribute> */
    public readonly array $itemAttributes;

    /** @var list<ZugferdItemClassification> */
    public readonly array $itemClassifications;

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
        $this->purchaseOrderLineReference = null;

        $delivery = $this->line->delivery;
        if ($delivery instanceof Party) {
            $this->shipToName = self::trimOrNull($delivery->name);
            $this->shipToAddress = new ZugferdAddressAdapter($delivery->address);
        } else {
            $this->shipToName = null;
            $this->shipToAddress = null;
        }

        $this->orderDocumentReference = self::trimOrNull($this->line->order_number ?? null);
        $this->deliveryNoteNumber = self::trimOrNull($this->line->delivery_note_number ?? null);
        $this->purchaseOrderDate = self::trimOrNull($this->line->order_date ?? null);
        $this->itemAttributes = LineItemAttributeMapper::attributes(
            is_string($this->line->material) ? $this->line->material : null,
            $this->line->weight_kg_net !== null ? (float) $this->line->weight_kg_net : null,
            $this->line->weight_kg_total !== null ? (float) $this->line->weight_kg_total : null,
            is_string($this->line->material_test_certificate) ? $this->line->material_test_certificate : null,
        );
        $customs = $this->line->customs_tariff_number;
        $this->itemClassifications = LineItemAttributeMapper::classifications(
            is_string($customs) ? $customs : null,
        );
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

    private static function trimOrNull(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed !== '' ? $trimmed : null;
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
        $reasonCode = $charge->reason_code !== null ? trim((string) $charge->reason_code) : '';
        if ($reasonCode === '' && LineAllowanceChargeResolver::isMaterialTestCertificateCharge($charge)) {
            $reasonCode = 'CAE';
        }

        return new AllowanceCharge(
            isCharge: (bool) $charge->is_charge,
            amount: (float) $charge->amount,
            reasonCode: $reasonCode !== '' ? $reasonCode : null,
            reasonText: $charge->reason_text,
            basisAmount: $charge->base_amount !== null ? (float) $charge->base_amount : null,
            percentage: $charge->percentage !== null ? (float) $charge->percentage : null,
        );
    }
}
