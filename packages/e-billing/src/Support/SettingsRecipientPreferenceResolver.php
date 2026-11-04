<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\RecipientPreferenceResolver;
use Moox\EBilling\Data\RecipientPreferences;
use Moox\EBilling\Enums\AttributionRole;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\EbillingSetting;

/**
 * Default resolver: the `ebilling_settings` row owned by the document's active `buyer_contact` attribution.
 * No contact, no row or no choice → no preference, so config applies.
 */
final class SettingsRecipientPreferenceResolver implements RecipientPreferenceResolver
{
    public function resolve(EbillingDocument $document): RecipientPreferences
    {
        $contact = $document->activeParty(AttributionRole::BuyerContact);

        if ($contact === null) {
            return new RecipientPreferences;
        }

        $setting = EbillingSetting::forReference($contact->type, $contact->id);

        return $setting?->preferred_ebilling_format?->preferences() ?? new RecipientPreferences;
    }
}
