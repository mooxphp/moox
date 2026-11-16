<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum InvoiceOriginRule: string
{
    case CountryDetected = 'country_detected';
    case CountryDetectedDe = 'country_detected_de';
    case NetOnly = 'net_only';
    case DefaultDe = 'default_de';
    case DomesticVatId = 'domestic_vat_id';
    case DomesticVatCharged = 'domestic_vat_charged';
    case UnclearForeignVatCharged = 'unclear_foreign_vat_charged';
    case UnclearDomesticVatIdWithoutVat = 'unclear_domestic_vat_id_without_vat';
    case UnclearNetOnly = 'unclear_net_only';

    /**
     * The signals contradict each other or one is missing: the document is generated, held for a person, and
     * the reviewer either approves it or marks it foreign (`e-billing.foreign.review_unclear`).
     */
    public function needsReview(): bool
    {
        return in_array($this, [
            self::UnclearForeignVatCharged,
            self::UnclearDomesticVatIdWithoutVat,
            self::UnclearNetOnly,
        ], true);
    }
}
