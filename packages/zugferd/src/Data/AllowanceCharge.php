<?php

declare(strict_types=1);

namespace Moox\Zugferd\Data;

use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;

readonly class AllowanceCharge implements ZugferdAllowanceCharge
{
    public function __construct(
        public bool $isCharge,
        public float $amount,
        public ?string $reasonCode = null,
        public ?string $reasonText = null,
        public ?float $basisAmount = null,
        public ?float $percentage = null,
    ) {}
}
