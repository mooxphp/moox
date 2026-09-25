<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\RecipientFormatPreferenceResolverInterface;
use Moox\EBilling\Data\FormatPreference;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Default host-bound preference: Customer.preferred_ebilling_format only.
 * Always returns profile null — hybrid profile comes from FormatDefinition / allowlist.
 */
final class CustomerFormatPreferenceResolver implements RecipientFormatPreferenceResolverInterface
{
    public function resolve(EbillingDocument $document): ?FormatPreference
    {
        $customer = (new CustomerMatcher)->forDocument($document);

        if ($customer === null) {
            return null;
        }

        $preferred = $customer->preferred_ebilling_format;

        if (! is_string($preferred) || $preferred === '') {
            return null;
        }

        return new FormatPreference($preferred);
    }
}
