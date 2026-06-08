<?php

declare(strict_types=1);

namespace Moox\Invoice\Support;

readonly class ChargeDraft
{
    public function __construct(
        public bool $is_charge,
        public float $amount,
        public ?string $reason_code = null,
        public ?string $reason_text = null,
        public ?float $base_amount = null,
        public ?float $percentage = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toCreateAttributes(): array
    {
        $attributes = [
            'is_charge' => $this->is_charge,
            'amount' => $this->amount,
        ];

        if ($this->reason_code !== null) {
            $attributes['reason_code'] = $this->reason_code;
        }

        if ($this->reason_text !== null) {
            $attributes['reason_text'] = $this->reason_text;
        }

        if ($this->base_amount !== null) {
            $attributes['base_amount'] = $this->base_amount;
        }

        if ($this->percentage !== null) {
            $attributes['percentage'] = $this->percentage;
        }

        return $attributes;
    }
}
