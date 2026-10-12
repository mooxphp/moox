<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * BR-CO-25: a positive amount due (BT-115) needs a due date (BT-9) or payment terms (BT-20). A credit
 * note (381, positive amounts per ADR 0010) usually prints neither, so the host's configured text is
 * emitted as BT-20. Emission only — the parsed document keeps its empty payment terms.
 */
final class CreditNotePaymentTerms
{
    public static function forEmission(string $documentTypeCode, ?string $dueDate, ?string $paymentTerms): ?string
    {
        if (self::isFilled($paymentTerms)) {
            return $paymentTerms;
        }

        if (! FieldValidationProfile::isCreditNote($documentTypeCode) || self::isFilled($dueDate)) {
            return null;
        }

        $fallback = config('e-billing.credit_note_payment_terms');

        return is_string($fallback) && trim($fallback) !== '' ? trim($fallback) : null;
    }

    private static function isFilled(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
