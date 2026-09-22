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


    public ?string $shipToName {
        get {
            $delivery = $this->line->delivery;
            if (! $delivery instanceof Party) {
                return null;
            }

            $name = trim($delivery->name);

            return $name !== '' ? $name : null;
        }
    }

    public ?ZugferdAddress $shipToAddress {
        get {
            $delivery = $this->line->delivery;
            if (! $delivery instanceof Party) {
                return null;
            }

            return new ZugferdAddressAdapter($delivery->address);
        }
    }

    public ?string $purchaseOrderLineReference {
        get => null;
    }

    public ?string $orderDocumentReference {
        get {
            $order = trim((string) ($this->line->order_number ?? ''));

            return $order !== '' ? $order : null;
        }
    }

    public ?string $deliveryNoteNumber {
        get {
            $value = trim((string) ($this->line->delivery_note_number ?? ''));

            return $value !== '' ? $value : null;
        }
    }

    public ?string $purchaseOrderDate {
        get {
            $value = trim((string) ($this->line->order_date ?? ''));

            return $value !== '' ? $value : null;
        }
    }

    /** @var list<ZugferdItemAttribute> */
    public array $itemAttributes {
        get {
            $material = $this->line->material;
            $material = is_string($material) ? $material : null;
            $net = $this->line->weight_kg_net !== null ? (float) $this->line->weight_kg_net : null;
            $gross = $this->line->weight_kg_total !== null ? (float) $this->line->weight_kg_total : null;
            $certificate = $this->line->material_test_certificate;
            $certificate = is_string($certificate) ? $certificate : null;

            return LineItemAttributeMapper::attributes($material, $net, $gross, $certificate);
        }
    }

    /** @var list<ZugferdItemClassification> */
    public array $itemClassifications {
        get {
            $customs = $this->line->customs_tariff_number;
            $customs = is_string($customs) ? $customs : null;

            return LineItemAttributeMapper::classifications($customs);
        }
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
