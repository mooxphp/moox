<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Zugferd\Contracts\ZugferdNoteLabels;
use Moox\Zugferd\ZugferdConverter;

/**
 * Line note (BT-127) labels in e-billing.document_locale, bound for {@see ZugferdConverter}.
 */
final class DocumentNoteLabels implements ZugferdNoteLabels
{
    public function date(string $value): string
    {
        return DocumentEmissionLabels::date($value);
    }

    public function quantity(float $value): string
    {
        return DocumentEmissionLabels::quantity($value);
    }

    public function purchaseOrder(): string
    {
        return DocumentEmissionLabels::note('purchase_order');
    }

    public function orderDate(): string
    {
        return DocumentEmissionLabels::note('order_date');
    }

    public function despatchAdvice(): string
    {
        return DocumentEmissionLabels::note('despatch_advice');
    }

    public function consignee(): string
    {
        return DocumentEmissionLabels::note('consignee');
    }
}
