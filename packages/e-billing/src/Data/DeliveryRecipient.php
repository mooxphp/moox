<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

final readonly class DeliveryRecipient
{
    public function __construct(
        public string $address,
        public ?string $name = null,
        public ?string $kind = null,
    ) {
    }
}
