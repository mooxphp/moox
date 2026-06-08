<?php

declare(strict_types=1);

namespace Moox\EBilling\Adapters;

use Moox\Invoice\Support\En16931\Address;
use Moox\Zugferd\Contracts\ZugferdAddress;

final class ZugferdAddressAdapter implements ZugferdAddress
{
    public function __construct(
        private Address $address,
    ) {}

    public ?string $street {
        get {
            $line1 = trim($this->address->line1);

            return $line1 !== '' ? $line1 : null;
        }
    }

    public ?string $addressLine2 {
        get {
            if ($this->address->line2 === null) {
                return null;
            }

            $line2 = trim($this->address->line2);

            return $line2 !== '' ? $line2 : null;
        }
    }

    public ?string $addressLine3 {
        get => null;
    }

    public ?string $zip {
        get {
            $postalCode = trim($this->address->postal_code);

            return $postalCode !== '' ? $postalCode : null;
        }
    }

    public ?string $city {
        get {
            $city = trim($this->address->city);

            return $city !== '' ? $city : null;
        }
    }

    public ?string $country {
        get {
            $countryCode = trim($this->address->country_code);

            return $countryCode !== '' ? $countryCode : null;
        }
    }
}
