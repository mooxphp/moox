<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\RecipientPreferences;
use Moox\EBilling\Models\EbillingDocument;

/**
 * The recipient's own choices for a document: e-invoice format and whether the visual copy goes along.
 * Each unset value falls back to config (`e-billing.default.format`, `e-billing.send_visual_copy`).
 */
interface RecipientPreferenceResolver
{
    public function resolve(EbillingDocument $document): RecipientPreferences;
}
