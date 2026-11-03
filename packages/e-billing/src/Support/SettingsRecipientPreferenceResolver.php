<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Moox\EBilling\Contracts\RecipientPreferenceResolver;
use Moox\EBilling\Data\RecipientPreferences;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\EbillingSetting;

/**
 * Default resolver: the `ebilling_settings` row of the document's contact (`contact_id`), whose model is
 * `e-billing.settings.contact_model`. No contact, no row or no choice → no preference, so config applies. A
 * misconfigured model throws, so the format resolver holds the document instead of silently using the default.
 */
final class SettingsRecipientPreferenceResolver implements RecipientPreferenceResolver
{
    public function resolve(EbillingDocument $document): RecipientPreferences
    {
        $contactModel = config('e-billing.settings.contact_model');

        if ($document->contact_id === null || $contactModel === null) {
            return new RecipientPreferences;
        }

        if (! is_string($contactModel) || ! is_a($contactModel, Model::class, true)) {
            throw new InvalidArgumentException('config(\'e-billing.settings.contact_model\') must name an Eloquent model.');
        }

        $setting = EbillingSetting::forReference((new $contactModel)->getMorphClass(), $document->contact_id);

        return $setting?->preferred_ebilling_format?->preferences() ?? new RecipientPreferences;
    }
}
