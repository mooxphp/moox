<?php

declare(strict_types=1);

namespace Moox\Zugferd\Contracts;

interface ZugferdAllowanceCharge
{
    public bool $isCharge { get; }

    public float $amount { get; }

    public ?string $reasonCode { get; }

    public ?string $reasonText { get; }

    public ?float $basisAmount { get; }

    public ?float $percentage { get; }
}
