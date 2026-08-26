<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Audit\Contracts\ActivitySubjectLabelResolver;
use Moox\Audit\Models\Activity;
use Moox\Audit\Support\ActivityEntryPresenter;

final class InvoiceActivitySubjectLabel implements ActivitySubjectLabelResolver
{
    private const CREDIT_NOTE_CODE = '381';

    public function resolve(Activity $activity): ?string
    {
        $invoiceNumber = self::stringAttribute($activity, 'invoice_number');

        if ($invoiceNumber === null) {
            return null;
        }

        $documentType = self::stringAttribute($activity, 'document_type') ?? '';

        $typeLabel = $documentType === self::CREDIT_NOTE_CODE
            ? __('e-billing::ebilling.credit_note')
            : __('e-billing::ebilling.invoice');

        return $typeLabel.' '.$invoiceNumber;
    }

    private static function stringAttribute(Activity $activity, string $attribute): ?string
    {
        $value = ActivityEntryPresenter::subjectAttributeValue($activity, $attribute);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_scalar($value) && (string) $value !== '') {
            return (string) $value;
        }

        return null;
    }
}
