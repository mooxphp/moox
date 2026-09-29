<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Invoice\Models\Invoice;

/**
 * A credit note (381) carries the direction in its type code, so its amounts must be positive.
 * A negative BT-112 reads as a debit and passes schema validation unnoticed (ADR 0010).
 */
final class CreditNoteSign
{
    public static function hasNegativeTotal(?Invoice $invoice): bool
    {
        if (! $invoice instanceof Invoice || ! FieldValidationProfile::isCreditNote($invoice->document_type)) {
            return false;
        }

        return (float) $invoice->gross_total < 0;
    }
}
