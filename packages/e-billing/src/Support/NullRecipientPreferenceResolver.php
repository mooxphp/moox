<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\RecipientPreferenceResolver;
use Moox\EBilling\Data\RecipientPreferences;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Default resolver: no recipient preferences, so the configured defaults apply.
 */
final class NullRecipientPreferenceResolver implements RecipientPreferenceResolver
{
    public function resolve(EbillingDocument $document): RecipientPreferences
    {
        return new RecipientPreferences;
    }
}
