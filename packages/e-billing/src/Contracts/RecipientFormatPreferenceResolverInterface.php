<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\FormatPreference;
use Moox\EBilling\Models\EbillingDocument;

interface RecipientFormatPreferenceResolverInterface
{
    /**
     * Return the recipient's format preference for this document, or null when
     * the host has no preference (orchestrator applies config e-billing.default).
     */
    public function resolve(EbillingDocument $document): ?FormatPreference;
}
