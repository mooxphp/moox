<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\EBilling\Support\BillDataAllowanceChargeMapper;
use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Contracts\ZugferdInvoiceLine;

class InvoiceLine implements ZugferdInvoiceLine
{
    public function __construct(
        public int $position = 0,
        public string $unit = '',
        public string $unitCode = '',
        public float $quantity = 0,
        public string $description = '',
        public ?string $descriptionDetail = null,
        public ?string $articleNumber = null,
        public ?string $material = null,
        public ?string $materialTestCertificate = null,
        public ?float $materialTestCertificatePrice = null,
        public ?string $customsTariffNumber = null,
        public ?float $weightKgTotal = null,
        public ?float $weightKgNet = null,
        public float $unitPrice = 0,
        public float $lineTotal = 0,
        public ?float $surchargeAmount = null,
        public ?string $surchargeDescription = null,
        public ?string $deliveryDate = null,
        public ?string $deliveryNoteNumber = null,
        public ?string $orderNumber = null,
        public ?string $orderDate = null,
        public ?Address $deliveryAddress = null,
    ) {
        if ($this->weightKgNet === null && $this->weightKgTotal !== null && $this->quantity > 0) {
            $this->weightKgNet = round($this->weightKgTotal / $this->quantity, 3);
        }
    }

    /** @var list<ZugferdAllowanceCharge> */
    public array $allowanceCharges {
        get {
            return BillDataAllowanceChargeMapper::fromLineScalars(
                $this->surchargeAmount,
                $this->surchargeDescription,
                $this->materialTestCertificatePrice,
                $this->materialTestCertificate,
            );
        }
    }

    public function totalWithSurcharge(): float
    {
        return $this->lineTotal
            + ($this->surchargeAmount ?? 0)
            + ($this->materialTestCertificatePrice ?? 0);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $deliveryAddr = isset($data['delivery_address']) && is_array($data['delivery_address'])
            ? Address::fromMixed($data['delivery_address'])
            : null;

        return new self(
            position: (int) ($data['position'] ?? 0),
            unit: is_string($data['unit'] ?? null) ? $data['unit'] : '',
            unitCode: is_string($data['unit_code'] ?? null) ? $data['unit_code'] : '',
            quantity: (float) ($data['quantity'] ?? 0),
            description: is_string($data['description'] ?? null) ? $data['description'] : '',
            descriptionDetail: isset($data['description_detail']) && is_string($data['description_detail']) ? $data['description_detail'] : null,
            articleNumber: isset($data['article_number']) && is_string($data['article_number']) ? $data['article_number'] : null,
            material: isset($data['material']) && is_string($data['material']) ? $data['material'] : null,
            materialTestCertificate: isset($data['material_test_certificate']) && is_string($data['material_test_certificate']) ? $data['material_test_certificate'] : null,
            materialTestCertificatePrice: isset($data['material_test_certificate_price']) && is_numeric($data['material_test_certificate_price'])
                ? (float) $data['material_test_certificate_price'] : null,
            customsTariffNumber: isset($data['customs_tariff_number']) && is_string($data['customs_tariff_number']) ? $data['customs_tariff_number'] : null,
            weightKgTotal: isset($data['weight_kg_total']) && is_numeric($data['weight_kg_total']) ? (float) $data['weight_kg_total'] : null,
            weightKgNet: isset($data['weight_kg_net']) && is_numeric($data['weight_kg_net']) ? (float) $data['weight_kg_net'] : null,
            unitPrice: (float) ($data['unit_price'] ?? 0),
            lineTotal: (float) ($data['line_total'] ?? 0),
            surchargeAmount: isset($data['surcharge_amount']) && is_numeric($data['surcharge_amount']) ? (float) $data['surcharge_amount'] : null,
            surchargeDescription: isset($data['surcharge_description']) && is_string($data['surcharge_description']) ? $data['surcharge_description'] : null,
            deliveryDate: isset($data['delivery_date']) && is_string($data['delivery_date']) ? $data['delivery_date'] : null,
            deliveryNoteNumber: isset($data['delivery_note_number']) && is_string($data['delivery_note_number']) ? $data['delivery_note_number'] : null,
            orderNumber: isset($data['order_number']) && is_string($data['order_number']) ? $data['order_number'] : null,
            orderDate: isset($data['order_date']) && is_string($data['order_date']) ? $data['order_date'] : null,
            deliveryAddress: $deliveryAddr,
        );
    }

    public function toArray(): array
    {
        return [
            'position' => $this->position,
            'unit' => $this->unit,
            'unit_code' => $this->unitCode,
            'quantity' => $this->quantity,
            'description' => $this->description,
            'description_detail' => $this->descriptionDetail,
            'article_number' => $this->articleNumber,
            'material' => $this->material,
            'material_test_certificate' => $this->materialTestCertificate,
            'material_test_certificate_price' => $this->materialTestCertificatePrice,
            'customs_tariff_number' => $this->customsTariffNumber,
            'weight_kg_total' => $this->weightKgTotal,
            'weight_kg_net' => $this->weightKgNet,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
            'surcharge_amount' => $this->surchargeAmount,
            'surcharge_description' => $this->surchargeDescription,
            'delivery_date' => $this->deliveryDate,
            'delivery_note_number' => $this->deliveryNoteNumber,
            'order_number' => $this->orderNumber,
            'order_date' => $this->orderDate,
            'delivery_address' => $this->deliveryAddress?->toArray(),
        ];
    }
}
