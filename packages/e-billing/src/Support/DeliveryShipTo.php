<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Data\Address;
use Moox\Zugferd\Contracts\ZugferdAddress;

final class DeliveryShipTo
{
    public static function name(?Address $deliveryAddress): ?string
    {
        $name = $deliveryAddress?->company;

        if ($name === null) {
            return null;
        }

        $trimmed = trim($name);

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function address(?Address $deliveryAddress): ?ZugferdAddress
    {
        return $deliveryAddress;
    }
}
