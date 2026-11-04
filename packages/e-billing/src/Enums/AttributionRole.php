<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

/**
 * What an attributed party is for the document (`ebilling_attributions.role`).
 */
enum AttributionRole: string
{
    /** The customer (debtor) the document is billed to; gates portal visibility. */
    case Buyer = 'buyer';

    /** The buyer's contact (the organisation); keys the recipient settings. */
    case BuyerContact = 'buyer_contact';

    public function label(): string
    {
        return __('e-billing::fields.attribution_role.'.$this->value);
    }
}
