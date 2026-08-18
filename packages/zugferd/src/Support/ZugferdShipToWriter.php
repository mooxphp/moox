<?php

declare(strict_types=1);

namespace Moox\Zugferd\Support;

use horstoeko\zugferd\ZugferdDocumentBuilder;
use Moox\Zugferd\Contracts\ZugferdAddress;
use Moox\Zugferd\Contracts\ZugferdInvoice;

final class ZugferdShipToWriter
{
    /**
     * @param  callable(ZugferdAddress): array{0: string, 1: string, 2: string}  $buildAddressLines
     */
    public static function write(
        ZugferdDocumentBuilder $doc,
        ZugferdInvoice $invoice,
        callable $buildAddressLines,
    ): void {
        $trimmedName = self::trimmedName($invoice->shipToName);
        $address = $invoice->shipToAddress;
        $country = $address !== null ? trim((string) ($address->country ?? '')) : '';

        $hasName = $trimmedName !== '';
        $hasCountry = $address !== null && $country !== '';

        if (! $hasName && ! $hasCountry) {
            return;
        }

        $doc->setDocumentShipTo($hasName ? $trimmedName : null);

        if (! $hasCountry) {
            return;
        }

        [$lineOne, $lineTwo, $lineThree] = $buildAddressLines($address);
        $doc->setDocumentShipToAddress(
            $lineOne,
            $lineTwo,
            $lineThree,
            $address->zip ?? '',
            $address->city ?? '',
            $address->country ?? '',
        );
    }

    private static function trimmedName(?string $name): string
    {
        return $name !== null ? trim($name) : '';
    }
}
