<?php

declare(strict_types=1);

namespace Moox\Zugferd\Contracts;

interface ZugferdAddress
{
    public ?string $street { get; }

    public ?string $addressLine2 { get; }

    public ?string $addressLine3 { get; }

    public ?string $zip { get; }

    public ?string $city { get; }

    public ?string $country { get; }
}
